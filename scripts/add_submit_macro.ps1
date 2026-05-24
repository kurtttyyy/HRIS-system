$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path ".").Path
$sourcePath = Join-Path $projectRoot "employee_submit_all_sheets_base.xlsx"
$targetPath = Join-Path $projectRoot "employee_submit_all_sheets_with_button.xlsm"
$tempPath = Join-Path $projectRoot "_macro_source_temp.xlsx"

if (-not (Test-Path $sourcePath)) {
    throw "Source workbook not found: $sourcePath"
}

$excel = $null
$workbook = $null

try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false

    Copy-Item -LiteralPath $sourcePath -Destination $tempPath -Force
    $workbook = $excel.Workbooks.Open($tempPath)
    $workbook.SaveAs($targetPath, 52)

    $module = $workbook.VBProject.VBComponents.Add(1)
    $module.Name = "SubmitEmployeeModule"
    $code = @'
Option Explicit

Public Sub SubmitEmployeeData()
    On Error GoTo ErrHandler

    Dim wsForm As Worksheet
    Dim wsEmp As Worksheet
    Dim employeeId As String
    Dim nextRow As Long
    Dim lo As ListObject

    Set wsForm = ThisWorkbook.Worksheets("Employee Data Sheet")
    Set wsEmp = ThisWorkbook.Worksheets("Employee")

    employeeId = Trim(CStr(wsForm.Range("A23").Value))
    If employeeId = "" Then
        MsgBox "Please enter employee information before submitting.", vbExclamation, "Submit Employee"
        Exit Sub
    End If

    If Application.WorksheetFunction.CountA(wsForm.Range("A23:AD23")) = 0 Then
        MsgBox "The generated row is empty. Please fill in the form first.", vbExclamation, "Submit Employee"
        Exit Sub
    End If

    If Application.WorksheetFunction.CountIf(wsEmp.Range("A:A"), employeeId) > 0 Then
        If MsgBox("Employee ID " & employeeId & " already exists. Add another row anyway?", vbQuestion + vbYesNo, "Duplicate Employee ID") = vbNo Then
            Exit Sub
        End If
    End If

    nextRow = wsEmp.Cells(wsEmp.Rows.Count, "A").End(xlUp).Row + 1
    If nextRow < 6 Then nextRow = 6

    If nextRow > 6 Then
        wsEmp.Rows(nextRow - 1).Copy
        wsEmp.Rows(nextRow).PasteSpecial Paste:=xlPasteFormats
        Application.CutCopyMode = False
    End If

    wsEmp.Range("A" & nextRow & ":AD" & nextRow).Value = wsForm.Range("A23:AD23").Value

    On Error Resume Next
    Set lo = wsEmp.ListObjects("EmployeeTable")
    On Error GoTo ErrHandler
    If Not lo Is Nothing Then
        lo.Resize wsEmp.Range("A5:AD" & nextRow)
    End If

    Application.CalculateFull
    wsEmp.Activate
    wsEmp.Range("A" & nextRow).Select

    MsgBox "Employee data saved to Employee page row " & nextRow & ".", vbInformation, "Submit Employee"
    Exit Sub

ErrHandler:
    MsgBox "Submit failed: " & Err.Description, vbCritical, "Submit Employee"
End Sub
'@
    $module.CodeModule.AddFromString($code)

    $ws = $workbook.Worksheets.Item("Employee Data Sheet")
    $buttonRange = $ws.Range("N18:P19")
    $button = $ws.Shapes.AddShape(5, $buttonRange.Left, $buttonRange.Top, $buttonRange.Width, $buttonRange.Height)
    $button.Name = "btnSubmitEmployee"
    $button.OnAction = "SubmitEmployeeData"
    $button.Fill.ForeColor.RGB = 0x2B3D00
    $button.Line.ForeColor.RGB = 0x3D9CCA
    $button.TextFrame2.TextRange.Text = "Submit"
    $button.TextFrame2.TextRange.Font.Bold = -1
    $button.TextFrame2.TextRange.Font.Size = 16
    $button.TextFrame2.TextRange.Font.Fill.ForeColor.RGB = 0xFFFFFF
    $button.TextFrame2.VerticalAnchor = 3
    $button.TextFrame2.TextRange.ParagraphFormat.Alignment = 2

    $workbook.Save()
    Write-Output $targetPath
}
finally {
    if ($workbook -ne $null) {
        $workbook.Close($true)
    }
    if ($excel -ne $null) {
        $excel.Quit()
    }
    if (Test-Path $tempPath) {
        Remove-Item -LiteralPath $tempPath -Force
    }
    [System.GC]::Collect()
    [System.GC]::WaitForPendingFinalizers()
}
