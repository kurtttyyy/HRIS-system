$ErrorActionPreference = "Stop"

$root = (Resolve-Path ".").Path
$inputPath = Join-Path $root "employee_dashboard_cover_beautiful_final_data_entry_20260513_170054_data_entry_fields_removed_license_label_updated_real_form_design.xlsx"
$outputPath = Join-Path $root "employee_dashboard_functioning_submit.xlsm"
$tempInputPath = Join-Path $root "_hris_macro_source.xlsx"

if (-not (Test-Path $inputPath)) {
    throw "Source workbook not found: $inputPath"
}

$vba = @'
Option Explicit

Private Const DATA_ENTRY_SHEET As String = "Data Entry"
Private Const EMPLOYEE_SHEET As String = "Employee"
Private Const FIRST_EMPLOYEE_ROW As Long = 6

Public Sub SubmitDataEntry()
    Dim dataSheet As Worksheet
    Dim employeeSheet As Worksheet
    Dim targetRow As Long

    Set dataSheet = ThisWorkbook.Worksheets(DATA_ENTRY_SHEET)
    Set employeeSheet = ThisWorkbook.Worksheets(EMPLOYEE_SHEET)

    If Trim(CStr(dataSheet.Range("E6").Value)) = "" And Trim(CStr(dataSheet.Range("E7").Value)) = "" Then
        MsgBox "Please enter at least a name or ID number.", vbExclamation, "Data Entry"
        Exit Sub
    End If

    targetRow = NextEmployeeRow(employeeSheet)

    With employeeSheet
        .Cells(targetRow, "B").Value = dataSheet.Range("E6").Value
        .Cells(targetRow, "C").Value = dataSheet.Range("E7").Value
        .Cells(targetRow, "D").Value = dataSheet.Range("E8").Value
        .Cells(targetRow, "E").Value = dataSheet.Range("E9").Value
        .Cells(targetRow, "F").Value = dataSheet.Range("E10").Value
        .Cells(targetRow, "G").Value = dataSheet.Range("E11").Value
        .Cells(targetRow, "H").Value = dataSheet.Range("E12").Value
        .Cells(targetRow, "I").Value = dataSheet.Range("E13").Value
        .Cells(targetRow, "J").Value = AgeText(dataSheet.Range("E13").Value)
        .Cells(targetRow, "K").Value = dataSheet.Range("M6").Value
        .Cells(targetRow, "L").Value = ServiceText(dataSheet.Range("M6").Value)
        .Cells(targetRow, "M").Value = dataSheet.Range("M8").Value
        .Cells(targetRow, "N").Value = dataSheet.Range("M9").Value
        .Cells(targetRow, "O").Value = dataSheet.Range("M10").Value
        .Cells(targetRow, "R").Value = dataSheet.Range("E19").Value
        .Cells(targetRow, "S").Value = dataSheet.Range("E20").Value
        .Cells(targetRow, "T").Value = dataSheet.Range("E21").Value
        .Cells(targetRow, "U").Value = dataSheet.Range("E22").Value
        .Cells(targetRow, "V").Value = dataSheet.Range("E23").Value
        .Cells(targetRow, "X").Value = dataSheet.Range("M20").Value
        .Cells(targetRow, "Y").Value = dataSheet.Range("M21").Value
        .Cells(targetRow, "Z").Value = dataSheet.Range("M22").Value
        .Cells(targetRow, "AA").Value = dataSheet.Range("E28").Value
        .Cells(targetRow, "AC").Value = dataSheet.Range("E30").Value
        .Cells(targetRow, "AD").Value = dataSheet.Range("E31").Value
        .Cells(targetRow, "AE").Value = dataSheet.Range("E32").Value
        .Cells(targetRow, "AF").Value = dataSheet.Range("M28").Value
        .Cells(targetRow, "AG").Value = dataSheet.Range("M29").Value
        .Cells(targetRow, "AH").Value = dataSheet.Range("M30").Value
    End With

    ClearDataEntry dataSheet
    ThisWorkbook.RefreshAll
    Application.CalculateFull
    MsgBox "Employee record saved to the Employee sheet.", vbInformation, "Data Entry"
End Sub

Private Function NextEmployeeRow(ByVal ws As Worksheet) As Long
    Dim rowNumber As Long

    rowNumber = FIRST_EMPLOYEE_ROW
    Do While Len(Trim(CStr(ws.Cells(rowNumber, "B").Value))) > 0 Or Len(Trim(CStr(ws.Cells(rowNumber, "C").Value))) > 0
        rowNumber = rowNumber + 1
    Loop

    NextEmployeeRow = rowNumber
End Function

Private Sub ClearDataEntry(ByVal ws As Worksheet)
    ws.Range("E6:E13,M6:M12,E19:E23,M20:M22,E28:E32,M28:M30").ClearContents
End Sub

Private Function AgeText(ByVal birthDate As Variant) As String
    If Not IsDate(birthDate) Then
        AgeText = ""
        Exit Function
    End If

    AgeText = CStr(CompletedYears(CDate(birthDate), Date))
End Function

Private Function ServiceText(ByVal startDate As Variant) As String
    If Not IsDate(startDate) Then
        ServiceText = ""
        Exit Function
    End If

    ServiceText = CStr(CompletedYears(CDate(startDate), Date)) & " year(s)"
End Function

Private Function CompletedYears(ByVal startDate As Date, ByVal endDate As Date) As Long
    Dim years As Long

    years = DateDiff("yyyy", startDate, endDate)
    If DateSerial(Year(endDate), Month(startDate), Day(startDate)) > endDate Then
        years = years - 1
    End If

    If years < 0 Then years = 0
    CompletedYears = years
End Function
'@

$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

try {
    Copy-Item -LiteralPath $inputPath -Destination $tempInputPath -Force
    $workbook = $excel.Workbooks.Open($tempInputPath, 0, $false, 5, "", "", $false, 1, "", $false, $false, 0, $true, $false, 1)
    $workbook.SaveAs($outputPath, 52)

    $module = $workbook.VBProject.VBComponents.Add(1)
    $module.Name = "HRISDataEntry"
    $module.CodeModule.AddFromString($vba)

    $sheet = $workbook.Worksheets.Item("Data Entry")
    foreach ($shape in @($sheet.Shapes)) {
        if ($shape.Name -eq "SubmitDataEntryButton") {
            $shape.Delete()
        }
    }

    $range = $sheet.Range("N47:P49")
    $button = $sheet.Shapes.AddShape(5, $range.Left, $range.Top, $range.Width, $range.Height)
    $button.Name = "SubmitDataEntryButton"
    $button.TextFrame.Characters().Text = "Submit"
    $button.TextFrame.Characters().Font.Bold = $true
    $button.TextFrame.Characters().Font.Size = 14
    $button.Fill.ForeColor.RGB = 3868166
    $button.Line.ForeColor.RGB = 6249833
    $button.OnAction = "SubmitDataEntry"

    $workbook.Save()
    $workbook.Close($true)
}
finally {
    if ($workbook) {
        try { $workbook.Close($false) } catch {}
    }
    $excel.Quit()
    [System.Runtime.InteropServices.Marshal]::ReleaseComObject($excel) | Out-Null
    if (Test-Path $tempInputPath) {
        Remove-Item -LiteralPath $tempInputPath -Force
    }
}

Write-Output $outputPath
