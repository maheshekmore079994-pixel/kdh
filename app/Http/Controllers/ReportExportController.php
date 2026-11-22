<?php

namespace App\Http\Controllers;

use App\Services\ReportBuilder;
use App\Services\ReportService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function export(Request $request, ReportBuilder $builder, SettingsService $settings, string $report, string $format): Response
    {
        Gate::authorize('reports');
        abort_unless(ReportBuilder::exists($report) && in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        [$from, $to] = ReportService::resolveRange($request->query('period', 'month'), $request->query('from'), $request->query('to'));
        $data = $builder->build($report, $from, $to);
        $filename = Str::slug($data['title']).'-'.$from->format('Ymd').($data['ranged'] ? '-'.$to->format('Ymd') : '');

        if ($format === 'pdf') {
            return Pdf::loadView('pdf.report', ['data' => $data, 'business' => $settings->group('business'), 'from' => $from, 'to' => $to])
                ->setPaper('a4', count($data['columns']) > 6 ? 'landscape' : 'portrait')
                ->download($filename.'.pdf');
        }

        return new StreamedResponse(function () use ($data, $format) {
            $writer = $format === 'csv' ? new CsvWriter : new XlsxWriter;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([$data['title']]));
            if ($data['ranged']) {
                $writer->addRow(Row::fromValues(['Period', $data['from']->format('d M Y').' to '.$data['to']->format('d M Y')]));
            } else {
                $writer->addRow(Row::fromValues(['As of', now()->format('d M Y H:i')]));
            }
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(array_map(fn ($c) => $c['label'], $data['columns'])));
            foreach ($data['rows'] as $row) {
                $writer->addRow(Row::fromValues(array_map(fn ($c) => $this->cell($row[$c['key']] ?? '', $c['type']), $data['columns'])));
            }
            if ($data['totals']) {
                $writer->addRow(Row::fromValues(array_map(fn ($c) => isset($data['totals'][$c['key']]) ? $this->cell($data['totals'][$c['key']], $c['type']) : '', $data['columns'])));
            }
            if (! empty($data['summary'])) {
                $writer->addRow(Row::fromValues([]));
                foreach ($data['summary'] as [$label, $value]) {
                    $writer->addRow(Row::fromValues([$label, $value]));
                }
            }
            $writer->close();
        }, 200, [
            'Content-Type' => $format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.'.$format.'"',
        ]);
    }

    private function cell(mixed $value, string $type): mixed
    {
        return match ($type) {
            'money' => (float) $value,
            'int' => (int) $value,
            default => (string) $value,
        };
    }
}
