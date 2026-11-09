# Renders Office files for visual checking (PowerPoint/Word COM).
#   powershell -File docs/tools/render.ps1 -Pptx <file> -Out <dir>     slides -> PNG
#   powershell -File docs/tools/render.ps1 -Docx <file> -Out <dir>     update TOC/fields, save, export PDF
param([string]$Pptx, [string]$Docx, [string]$Out)

New-Item -ItemType Directory -Force -Path $Out | Out-Null

if ($Pptx) {
    $pp = New-Object -ComObject PowerPoint.Application
    $pres = $pp.Presentations.Open((Resolve-Path $Pptx).Path, $true, $false, $false)
    $i = 1
    foreach ($slide in $pres.Slides) {
        $slide.Export((Join-Path (Resolve-Path $Out).Path ("slide-{0:D2}.png" -f $i)), "PNG", 1600, 900)
        $i++
    }
    $pres.Close()
    $pp.Quit()
    Write-Output "exported $($i - 1) slides"
}

if ($Docx) {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0          # never wait on a dialog nobody can see
    $word.Options.UpdateLinksAtOpen = $false
    # Open(FileName, ConfirmConversions, ReadOnly, AddToRecentFiles, ..., Visible=false, OpenAndRepair=false, ..., NoEncodingDialog=true)
    $doc = $word.Documents.Open((Resolve-Path $Docx).Path, $false, $false, $false, '', '', $true, '', '', 0, 0, $false, $false, 0, $true)
    foreach ($toc in $doc.TablesOfContents) { $toc.Update() }
    $doc.Save()
    $pdf = Join-Path (Resolve-Path $Out).Path "manuscript.pdf"
    $doc.ExportAsFixedFormat($pdf, 17)
    $pages = $doc.ComputeStatistics(2)
    $doc.Close()
    $word.Quit()
    Write-Output "pdf: $pdf ($pages pages)"
}
