# KDH Logo – Process Log

Running log of everything tried, what failed, and the lessons. Newest entries at the bottom.

## 2026-11-07 – Session 1

### Input
- Only source: `WhatsApp Image 2026-11-07 at 1.16.20 AM (1).jpeg`, 1536x1024, a 3D signboard mockup
  (textured beige wall, warm light glow, black letters with gold bevels, tricolor swoosh, gold pixels,
  tagline pill, services strip at the bottom).

### Environment findings
- `python` on PATH is the Microsoft Store stub. Real interpreter:
  `C:\Users\admin\AppData\Local\Programs\Python\Python312\python.exe`.
- pip failed with `CERTIFICATE_VERIFY_FAILED`: the Avast antivirus proxy re-signs HTTPS.
  Fix that keeps verification on: `pip install --use-feature=truststore ...` (uses the Windows cert store).
  `--trusted-host` was refused by the safety policy, and is the wrong fix anyway.
- rembg model could not be auto-downloaded (same TLS issue); fetched with curl into `~/.u2net/u2net.onnx`.
- Installed: pillow, numpy, opencv-python-headless, fonttools, uharfbuzz, resvg-py, rembg.

### Attempt 1 – rembg (AI background removal)
- Result: bad. u2net treats the whole lit wall area as "subject" and keeps the glow and vignette.
- Lesson: generic salient-object models do not work for a logo on a textured, lit wall.

### Attempt 2 – vector rebuild (SVG, Montserrat fonts, hand-built K and ribbon)
- Produced a clean flat logo package (stacked, horizontal, mark, icons, favicon).
- User reaction: rejected. "Worst logo extract", "like a sketch, we need something real".
- Lesson (important): "extract the logo" means the ORIGINAL pixels, lifted off the background,
  keeping the 3D look. A recreation is not what was asked. Also: show one preview and confirm the
  direction before producing a whole package.

### Attempt 3 – colour-threshold cut-out (dark OR saturated pixels, border components removed)
- Better than rembg. Remaining problems: yellow glow halo hugging the letters, soft/aliased edges,
  small specks, white band partly lost.
- Lesson: a plain threshold cannot separate the glow from the gold bevels; it needs background
  estimation plus edge refinement.

### Attempt 4 – high-quality raster extraction (in progress)
- Plan: work at 2x resolution, estimate the wall as a smooth background, mask by difference +
  darkness + saturation, remove border-touching regions, fill the white band, refine the edge with
  GrabCut, de-halo edge pixels by subtracting the estimated background, light feathering.

### Attempt 4 – result
- Median-background + GrabCut extraction keeps the real 3D rendering (bevels, gold, ribbon shading).
  User-facing quality is far above attempts 1-3.
- Problems seen in review: (1) the warm wall glow next to letters is kept, which looks like a halo on
  white; (2) mark-only crop came out empty because the K touched the crop border and the
  "drop border-touching regions" rule removed it.
- Fixes: a "tight" mode (stricter saturation/difference thresholds, smaller GrabCut search band,
  final mask limited to the strict mask) for a no-glow version; the mark is now cut from the
  full-logo result instead of being extracted separately. Both glow and tight versions are kept.
- Lesson: review every output image before handing over; a rule that works on one crop
  (border filter) can silently delete the subject on another.

### Attempt 5 – glow removal by colour (final)
- "Tight" thresholds (attempt 4 fix) chipped the ribbon tips and letter edges while glow still
  remained between letters. Dropped.
- Instead: keep the glow-version mask (correct shapes), then remove only pale-yellow glow pixels
  (hue 8-40, value > 140, saturation < 170; white band protected), open 3x3, drop specks < 300 px,
  fill holes < 200 px, 0.7 px feather. Compared saturation cut-offs 120 / 145 / 170 side by side;
  170 removed the glow and kept every gold bevel.
- Result approved internally on white and black. Faint light rim on black is residual bevel
  highlight, not wall.
- Lesson: when a mask has the right shape but wrong extras, subtract the extras by their own
  colour signature rather than tightening the whole mask.

### Deliverables now
- `logo/extracted/` = real pixels from the photo (main deliverable).
- `logo/vector-rebuild/` = the earlier flat SVG rebuild, kept only for reference (user rejected it).
