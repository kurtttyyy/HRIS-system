Option Explicit
Dim wordApp, wordDocument, sourcePath, outputPath, errorCode, errorMessage
If WScript.Arguments.Count <> 2 Then
    WScript.StdErr.WriteLine "Expected source and output paths."
    WScript.Quit 1
End If
sourcePath = Replace(WScript.Arguments(0), "/", "\")
outputPath = Replace(WScript.Arguments(1), "/", "\")
On Error Resume Next
Set wordApp = CreateObject("Word.Application")
If Err.Number <> 0 Then
    WScript.StdErr.WriteLine "Cannot start Microsoft Word: " & Err.Description
    WScript.Quit 1
End If
wordApp.Visible = False
wordApp.DisplayAlerts = 0
wordApp.AutomationSecurity = 3
wordApp.Options.UpdateLinksAtOpen = False
wordApp.Options.SaveNormalPrompt = False
Err.Clear
Set wordDocument = wordApp.Documents.Open(sourcePath, False, True, False, "preview-no-password", "preview-no-password", False, "preview-no-password", "preview-no-password")
If Err.Number = 0 Then
    wordDocument.ExportAsFixedFormat outputPath, 17, False
End If
errorCode = Err.Number
errorMessage = Err.Description
If IsObject(wordDocument) Then wordDocument.Close 0
wordApp.Quit 0
Set wordDocument = Nothing
Set wordApp = Nothing
If errorCode <> 0 Then
    WScript.StdErr.WriteLine "Cannot export Word document: " & errorMessage
    WScript.Quit 1
End If
WScript.Quit 0
