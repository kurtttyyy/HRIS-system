$ErrorActionPreference = "Stop"
$root = (Resolve-Path ".").Path
$path = Join-Path $root "_excel_test.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false
$wb = $excel.Workbooks.Add()
$wb.SaveAs($path, 51)
$wb.Close($true)
$excel.Quit()
Write-Output $path
