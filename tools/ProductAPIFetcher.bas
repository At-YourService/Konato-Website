Attribute VB_Name = "ProductAPIFetcher"
Option Explicit

' ============================================================
' CONFIGURATION — update before use
' ============================================================
Private Const API_BASE_URL As String = "https://your-api-endpoint.com/quotes/"
Private Const API_KEY      As String = "your-api-key-here"

' Sheet names (must match the template exactly)
Private Const SHEET_CALC As String = "Berekening"
Private Const SHEET_KEYS As String = "# Rekensleutels"

' Berekening column positions
Private Const C_NAAM     As Integer = 1   ' A  Omschrijving licentie
Private Const C_START_EU As Integer = 2   ' B  Startbedrag (€)
Private Const C_KORTING  As Integer = 3   ' C  Korting
Private Const C_PRIJS    As Integer = 4   ' D  Prijs (€)
Private Const C_IC_KORT  As Integer = 5   ' E  Geschatte IC-korting
Private Const C_IC_BED   As Integer = 6   ' F  IC-bedrag na korting (€)
Private Const C_IC_MARGE As Integer = 7   ' G  Geschatte marge IC
Private Const C_START_US As Integer = 8   ' H  Startbedrag ($)
Private Const C_KOST_US  As Integer = 9   ' I  Kost Abano ($)
Private Const C_AB_KORT  As Integer = 10  ' J  Korting Abano
Private Const C_KOST_EU  As Integer = 11  ' K  Geschatte kost Abano (€)
Private Const C_AB_MARGE As Integer = 12  ' L  Geschatte marge Abano (€)

Private Const FIRST_DATA_ROW As Integer = 3

' ============================================================
' DATA TYPE  (Private — only used inside this module)
' ============================================================
Private Type ProductData
    ProductName  As String
    ListPrice    As Double
    PartnerPrice As Double
End Type

' ============================================================
' MODULE-LEVEL STORAGE
' ParseAtlassianQuote writes here; all other subs read from here.
' This avoids returning UDT arrays from Functions (not supported in VBA).
' ============================================================
Private mProducts()   As ProductData
Private mProductCount As Long
Private mQuoteName    As String
Private mQuoteNumber  As String
Private mCustomerName As String

' ============================================================
' PUBLIC ENTRY POINTS  — assign one of these to each button
' ============================================================

' Button: Fetch quote by UUID from the Atlassian API
Public Sub FetchFromAPI()
    Dim quoteId  As String
    Dim jsonText As String

    quoteId = InputBox("Enter the Atlassian Quote ID (UUID):", "Fetch Quote", _
                       "546cc9c7-ec7a-4edb-8042-c1f8035bb310")
    If Len(Trim(quoteId)) = 0 Then Exit Sub

    On Error GoTo ErrHandler
    Application.ScreenUpdating = False
    Application.Cursor = xlWait

    jsonText = CallAPI(API_BASE_URL & quoteId, API_KEY)
    If Len(Trim(jsonText)) = 0 Then
        MsgBox "The API returned an empty response." & vbCrLf & _
               "Check API_BASE_URL and API_KEY.", vbExclamation, "No Response"
        GoTo Cleanup
    End If

    ProcessQuoteJSON jsonText

Cleanup:
    Application.Cursor = xlDefault
    Application.ScreenUpdating = True
    Exit Sub
ErrHandler:
    Application.Cursor = xlDefault
    Application.ScreenUpdating = True
    MsgBox "Error " & Err.Number & ": " & Err.Description, vbCritical, "Error"
End Sub

' Button: Load a local JSON file (for testing / offline use)
Public Sub LoadFromFile()
    Dim fd       As FileDialog
    Dim filePath As String
    Dim jsonText As String
    Dim lineText As String
    Dim fileNum  As Integer

    Set fd = Application.FileDialog(msoFileDialogFilePicker)
    fd.Title = "Select Atlassian Quote JSON file"
    fd.Filters.Clear
    fd.Filters.Add "JSON files", "*.json"
    fd.AllowMultiSelect = False
    If fd.Show <> -1 Then Exit Sub

    filePath = fd.SelectedItems(1)
    fileNum = 0

    On Error GoTo ErrHandler
    Application.ScreenUpdating = False
    Application.Cursor = xlWait

    fileNum = FreeFile
    Open filePath For Input As #fileNum
    Do While Not EOF(fileNum)
        Line Input #fileNum, lineText
        jsonText = jsonText & lineText & vbCrLf
    Loop
    Close #fileNum
    fileNum = 0

    ProcessQuoteJSON jsonText

Cleanup:
    Application.Cursor = xlDefault
    Application.ScreenUpdating = True
    Exit Sub
ErrHandler:
    Application.Cursor = xlDefault
    Application.ScreenUpdating = True
    If fileNum > 0 Then Close #fileNum
    MsgBox "Error " & Err.Number & ": " & Err.Description, vbCritical, "Error"
End Sub

' ============================================================
' ORCHESTRATION
' ============================================================
Private Sub ProcessQuoteJSON(jsonText As String)
    Dim wsCalc As Worksheet

    ' Parse into module-level mProducts / mProductCount
    ParseAtlassianQuote jsonText

    If mProductCount = 0 Then
        MsgBox "No billable line items found." & vbCrLf & _
               "(Expects QuoteDetails.upcomingBills.lines with subTotal > 0)", _
               vbInformation, "No Products"
        Exit Sub
    End If

    Set wsCalc = ThisWorkbook.Sheets(SHEET_CALC)
    WriteToBerekening wsCalc

    wsCalc.Activate
    wsCalc.Cells(FIRST_DATA_ROW, C_NAAM).Select

    MsgBox mProductCount & " product(s) written to '" & SHEET_CALC & "'." & vbCrLf & _
           "Quote:    " & mQuoteNumber & vbCrLf & _
           "Customer: " & mCustomerName, vbInformation, "Import Complete"
End Sub

' ============================================================
' HTTP REQUEST
' ============================================================
Private Function CallAPI(url As String, apiKey As String) As String
    Dim http As Object
    Set http = CreateObject("MSXML2.XMLHTTP.6.0")

    With http
        .Open "GET", url, False
        .setRequestHeader "Content-Type",  "application/json"
        .setRequestHeader "Accept",        "application/json"
        .setRequestHeader "X-API-Key",     apiKey
        ' .setRequestHeader "Authorization", "Bearer " & apiKey
        .send
        If .Status = 200 Then
            CallAPI = .responseText
        Else
            Err.Raise vbObjectError + 1000, "CallAPI", _
                "HTTP " & .Status & " " & .StatusText
        End If
    End With
    Set http = Nothing
End Function

' ============================================================
' ATLASSIAN QUOTE PARSER
' Populates module-level mProducts(), mProductCount, and metadata.
' Path: QuoteDetails → upcomingBills → lines[] where subTotal > 0
' Amounts are in cents — divided by 100 to get USD.
' ============================================================
Private Sub ParseAtlassianQuote(json As String)
    Dim i          As Long
    Dim idx        As Long
    Dim objText    As String
    Dim subTotStr  As String
    Dim subTot     As Double
    Dim amtStr     As String
    Dim marginAmt  As Double
    Dim qd         As String
    Dim ig         As String
    Dim shipTo     As String
    Dim bills      As String
    Dim linesArr   As String
    Dim lineObjs() As String
    Dim margJson   As String
    Dim margInner  As String
    Dim margObjs() As String

    ' Reset module-level storage
    mProductCount = 0
    mQuoteName    = ""
    mQuoteNumber  = ""
    mCustomerName = ""
    ReDim mProducts(0 To 0)

    ' Navigate to QuoteDetails
    qd = ExtractSection(json, "QuoteDetails")
    If Len(qd) = 0 Then
        MsgBox "Could not find 'QuoteDetails'. Is this an Atlassian quote payload?", _
               vbExclamation, "Parse Error"
        Exit Sub
    End If

    ' Quote metadata
    mQuoteName   = JSONStringValue(qd, "name")
    mQuoteNumber = JSONStringValue(qd, "number")

    ig = ExtractSection(json, "InvoiceGroup")
    If Len(ig) > 0 Then
        shipTo = ExtractSection(ig, "shipToParty")
        mCustomerName = JSONStringValue(shipTo, "name")
    End If

    ' Navigate to upcomingBills → lines
    bills = ExtractSection(qd, "upcomingBills")
    If Len(bills) = 0 Then
        MsgBox "Could not find 'upcomingBills' inside QuoteDetails.", _
               vbExclamation, "Parse Error"
        Exit Sub
    End If

    linesArr = ExtractSection(bills, "lines")
    If Len(linesArr) = 0 Then Exit Sub

    ' Strip outer [ ]
    If Left(linesArr, 1) = "[" Then linesArr = Mid(linesArr, 2)
    If Right(linesArr, 1) = "]" Then linesArr = Left(linesArr, Len(linesArr) - 1)

    lineObjs = SplitObjects(linesArr)
    If UBound(lineObjs) < 0 Then Exit Sub

    ReDim mProducts(0 To UBound(lineObjs))
    idx = 0

    For i = 0 To UBound(lineObjs)
        objText = Trim(lineObjs(i))
        If Len(objText) = 0 Then GoTo SkipLine

        ' Only billable lines (subTotal > 0)
        subTotStr = JSONNumberValue(objText, "subTotal")
        If Len(subTotStr) = 0 Then GoTo SkipLine
        subTot = CDbl(subTotStr)
        If subTot <= 0 Then GoTo SkipLine

        ' Get partner discount amount from margins[0].amount
        marginAmt = 0
        margJson = ExtractSection(objText, "margins")
        If Len(margJson) > 0 Then
            margInner = margJson
            If Left(margInner, 1) = "[" Then margInner = Mid(margInner, 2)
            If Right(margInner, 1) = "]" Then margInner = Left(margInner, Len(margInner) - 1)
            margObjs = SplitObjects(margInner)
            If UBound(margObjs) >= 0 Then
                amtStr = JSONNumberValue(margObjs(0), "amount")
                If Len(amtStr) > 0 Then marginAmt = CDbl(amtStr)
            End If
        End If

        mProducts(idx).ProductName  = JSONStringValue(objText, "description")
        mProducts(idx).ListPrice    = subTot / 100
        mProducts(idx).PartnerPrice = (subTot - marginAmt) / 100

        If Len(mProducts(idx).ProductName) > 0 Then
            idx = idx + 1
        End If
SkipLine:
    Next i

    mProductCount = idx
    If idx > 0 Then ReDim Preserve mProducts(0 To idx - 1)
End Sub

' ============================================================
' WRITE TO BEREKENING SHEET
' ============================================================
Private Sub WriteToBerekening(wsCalc As Worksheet)
    Dim totaalRow    As Long
    Dim lastProdRow  As Long
    Dim existingRows As Long
    Dim i            As Long
    Dim r            As Long
    Dim answer       As VbMsgBoxResult

    ' Find existing Totaal row
    totaalRow = FindTotaalRow(wsCalc)
    If totaalRow = 0 Then
        totaalRow = wsCalc.Cells(wsCalc.Rows.Count, C_NAAM).End(xlUp).Row + 1
    End If

    ' Confirm replacement of existing product rows
    existingRows = totaalRow - FIRST_DATA_ROW
    If existingRows > 0 Then
        answer = MsgBox(existingRows & " existing product row(s) found." & vbCrLf & _
                        "Replace with " & mProductCount & " product(s) from the quote?", _
                        vbYesNo + vbQuestion, "Existing Data")
        If answer = vbNo Then Exit Sub
        wsCalc.Rows(FIRST_DATA_ROW & ":" & totaalRow - 1).Delete Shift:=xlUp
        totaalRow = FIRST_DATA_ROW
    End If

    ' Insert blank rows for new products
    wsCalc.Rows(totaalRow & ":" & totaalRow + mProductCount - 1).Insert Shift:=xlDown

    ' Write each product row
    For i = 0 To mProductCount - 1
        r = FIRST_DATA_ROW + i
        WriteProductRow wsCalc, r, i
    Next i

    ' Update Totaal row and formatting
    totaalRow   = FIRST_DATA_ROW + mProductCount
    lastProdRow = totaalRow - 1

    UpdateTotaalRow   wsCalc, totaalRow, FIRST_DATA_ROW, lastProdRow
    FormatProductRange wsCalc, FIRST_DATA_ROW, totaalRow
    UpdateMetadataRow wsCalc

    wsCalc.Columns("A:L").AutoFit
End Sub

' Write one product row; productIdx references mProducts()
Private Sub WriteProductRow(wsCalc As Worksheet, r As Long, productIdx As Long)
    Dim rk As String
    rk = "'" & SHEET_KEYS & "'"

    ' Values from API
    wsCalc.Cells(r, C_NAAM).Value     = mProducts(productIdx).ProductName
    wsCalc.Cells(r, C_START_US).Value = mProducts(productIdx).ListPrice
    wsCalc.Cells(r, C_KOST_US).Value  = mProducts(productIdx).PartnerPrice

    ' Calculated formulas — identical to the original template
    wsCalc.Cells(r, C_START_EU).Formula = _
        "=IF(H" & r & "=""/""," & Chr(34) & "/" & Chr(34) & "," & _
        rk & "!$C$3*(1+" & rk & "!$C$2)*H" & r & ")"

    wsCalc.Cells(r, C_KORTING).Formula = _
        "=IF(J" & r & "<10%,0,IF(J" & r & "<20%,0.05,IF(J" & r & "<25%,0.075,0.1)))"

    wsCalc.Cells(r, C_PRIJS).Formula = _
        "=IF(B" & r & "=""/""," & Chr(34) & "/" & Chr(34) & ",B" & r & "*(1-C" & r & "))"

    wsCalc.Cells(r, C_IC_KORT).Formula = _
        "=IF(J" & r & "<5%,J" & r & "/2,0.025)"

    wsCalc.Cells(r, C_IC_BED).Formula = "=D" & r & "*(1-E" & r & ")"
    wsCalc.Cells(r, C_IC_MARGE).Formula = "=D" & r & "-F" & r
    wsCalc.Cells(r, C_AB_KORT).Formula = "=-(I" & r & "/H" & r & "-1)"

    wsCalc.Cells(r, C_KOST_EU).Formula = _
        "=IF(I" & r & "=""/""," & Chr(34) & "/" & Chr(34) & ",I" & r & "*" & rk & "!$C$3)"

    wsCalc.Cells(r, C_AB_MARGE).Formula = _
        "=IF($D$2=""Neen"",D" & r & "-K" & r & ",F" & r & "-K" & r & ")"
End Sub

' Rewrite the Totaal SUM row
Private Sub UpdateTotaalRow(wsCalc As Worksheet, totaalRow As Long, _
                             firstRow As Long, lastRow As Long)
    Dim j       As Integer
    Dim col     As Integer
    Dim sumCols As Variant

    sumCols = Array(C_START_EU, C_PRIJS, C_IC_BED, C_IC_MARGE, _
                    C_START_US, C_KOST_US, C_KOST_EU, C_AB_MARGE)

    wsCalc.Cells(totaalRow, C_NAAM).Value    = "Totaal:"
    wsCalc.Cells(totaalRow, C_NAAM).Font.Bold = True
    wsCalc.Cells(totaalRow, C_KORTING).Value  = "/"
    wsCalc.Cells(totaalRow, C_IC_KORT).Value  = "/"
    wsCalc.Cells(totaalRow, C_AB_KORT).Value  = "/"

    For j = 0 To UBound(sumCols)
        col = sumCols(j)
        wsCalc.Cells(totaalRow, col).Formula = _
            "=SUM(" & ColLetter(col) & firstRow & ":" & ColLetter(col) & lastRow & ")"
        wsCalc.Cells(totaalRow, col).Font.Bold = True
    Next j
End Sub

' Write quote name, customer and quote number into metadata row 1
Private Sub UpdateMetadataRow(wsCalc As Worksheet)
    If Len(mCustomerName) > 0 Then
        wsCalc.Cells(1, C_NAAM).Value     = "Eindklant: " & mCustomerName
    End If
    If Len(mQuoteNumber) > 0 Then
        wsCalc.Cells(1, C_START_US).Value = "Jira-ticket: " & mQuoteNumber
    End If
    If Len(mQuoteName) > 0 Then
        wsCalc.Cells(1, C_START_EU).Value = mQuoteName
    End If
End Sub

' Apply currency and percentage formats to product rows + Totaal row
Private Sub FormatProductRange(wsCalc As Worksheet, firstRow As Long, lastRow As Long)
    Dim i       As Integer
    Dim eurCols As Variant

    eurCols = Array(C_START_EU, C_PRIJS, C_IC_BED, C_IC_MARGE, C_KOST_EU, C_AB_MARGE)
    For i = 0 To UBound(eurCols)
        wsCalc.Range(wsCalc.Cells(firstRow, eurCols(i)), _
                     wsCalc.Cells(lastRow,  eurCols(i))).NumberFormat = _
            Chr(34) & Chr(8364) & " " & Chr(34) & "#,##0.00"
    Next i

    wsCalc.Range(wsCalc.Cells(firstRow, C_START_US), _
                 wsCalc.Cells(lastRow,  C_START_US)).NumberFormat = _
        Chr(34) & "$ " & Chr(34) & "#,##0.00"

    wsCalc.Range(wsCalc.Cells(firstRow, C_KOST_US), _
                 wsCalc.Cells(lastRow,  C_KOST_US)).NumberFormat = _
        Chr(34) & "$ " & Chr(34) & "#,##0.00"

    wsCalc.Range(wsCalc.Cells(firstRow, C_KORTING), _
                 wsCalc.Cells(lastRow,  C_KORTING)).NumberFormat = "0.00%"
    wsCalc.Range(wsCalc.Cells(firstRow, C_IC_KORT), _
                 wsCalc.Cells(lastRow,  C_IC_KORT)).NumberFormat = "0.00%"
    wsCalc.Range(wsCalc.Cells(firstRow, C_AB_KORT), _
                 wsCalc.Cells(lastRow,  C_AB_KORT)).NumberFormat = "0.00%"
End Sub

' Return the row number of the Totaal row (searches col A for "Totaal")
Private Function FindTotaalRow(wsCalc As Worksheet) As Long
    Dim lastRow As Long
    Dim r       As Long
    lastRow = wsCalc.Cells(wsCalc.Rows.Count, C_NAAM).End(xlUp).Row
    For r = FIRST_DATA_ROW To lastRow
        If InStr(1, wsCalc.Cells(r, C_NAAM).Value, "Totaal", vbTextCompare) > 0 Then
            FindTotaalRow = r
            Exit Function
        End If
    Next r
    FindTotaalRow = 0
End Function

' ============================================================
' JSON HELPERS
' ============================================================

' Extract a named { } or [ ] section, balanced and string-aware
Private Function ExtractSection(json As String, key As String) As String
    Dim searchFor As String
    Dim pos       As Long
    Dim opener    As String
    Dim closer    As String
    Dim depth     As Integer
    Dim inQuote   As Boolean
    Dim i         As Long
    Dim ch        As String

    searchFor = Chr(34) & key & Chr(34) & ":"
    pos = InStr(1, json, searchFor, vbTextCompare)
    If pos = 0 Then Exit Function

    pos = pos + Len(searchFor)

    ' Skip whitespace
    Do While pos <= Len(json)
        ch = Mid(json, pos, 1)
        If ch <> " " And ch <> Chr(9) And ch <> Chr(10) And ch <> Chr(13) Then Exit Do
        pos = pos + 1
    Loop

    opener = Mid(json, pos, 1)
    If opener = "{" Then
        closer = "}"
    ElseIf opener = "[" Then
        closer = "]"
    Else
        Exit Function
    End If

    depth = 0
    inQuote = False

    For i = pos To Len(json)
        ch = Mid(json, i, 1)
        If inQuote Then
            If ch = "\" Then
                i = i + 1
            ElseIf ch = Chr(34) Then
                inQuote = False
            End If
        Else
            If ch = Chr(34) Then
                inQuote = True
            ElseIf ch = opener Then
                depth = depth + 1
            ElseIf ch = closer Then
                depth = depth - 1
                If depth = 0 Then
                    ExtractSection = Mid(json, pos, i - pos + 1)
                    Exit Function
                End If
            End If
        End If
    Next i
End Function

' Split a JSON body (no outer [ ]) into individual { } objects, string-aware
Private Function SplitObjects(json As String) As String()
    Dim result()   As String
    Dim emptyArr() As String
    Dim depth      As Integer
    Dim inQuote    As Boolean
    Dim i          As Long
    Dim startPos   As Long
    Dim count      As Long
    Dim ch         As String

    ReDim result(0 To 0)
    ReDim emptyArr(-1 To -1)

    count    = 0
    depth    = 0
    inQuote  = False
    startPos = 1

    For i = 1 To Len(json)
        ch = Mid(json, i, 1)
        If inQuote Then
            If ch = "\" Then
                i = i + 1
            ElseIf ch = Chr(34) Then
                inQuote = False
            End If
        Else
            If ch = Chr(34) Then
                inQuote = True
            ElseIf ch = "{" Then
                If depth = 0 Then startPos = i
                depth = depth + 1
            ElseIf ch = "}" Then
                depth = depth - 1
                If depth = 0 Then
                    ReDim Preserve result(0 To count)
                    result(count) = Mid(json, startPos, i - startPos + 1)
                    count = count + 1
                End If
            End If
        End If
    Next i

    If count = 0 Then
        SplitObjects = emptyArr
    Else
        ReDim Preserve result(0 To count - 1)
        SplitObjects = result
    End If
End Function

' Return the string value for a JSON key (handles escaped quotes in value)
Private Function JSONStringValue(obj As String, key As String) As String
    Dim searchFor As String
    Dim pos       As Long
    Dim endPos    As Long
    Dim ch        As String

    searchFor = Chr(34) & key & Chr(34) & ":"
    pos = InStr(1, obj, searchFor, vbTextCompare)
    If pos = 0 Then Exit Function

    pos = pos + Len(searchFor)
    Do While pos <= Len(obj)
        ch = Mid(obj, pos, 1)
        If ch <> " " And ch <> Chr(9) Then Exit Do
        pos = pos + 1
    Loop
    If Mid(obj, pos, 1) <> Chr(34) Then Exit Function
    pos = pos + 1

    endPos = pos
    Do While endPos <= Len(obj)
        ch = Mid(obj, endPos, 1)
        If ch = "\" Then
            endPos = endPos + 2
        ElseIf ch = Chr(34) Then
            Exit Do
        Else
            endPos = endPos + 1
        End If
    Loop

    JSONStringValue = Mid(obj, pos, endPos - pos)
End Function

' Return the raw text of a numeric JSON value for a key
Private Function JSONNumberValue(obj As String, key As String) As String
    Dim searchFor As String
    Dim pos       As Long
    Dim endPos    As Long
    Dim ch        As String

    searchFor = Chr(34) & key & Chr(34) & ":"
    pos = InStr(1, obj, searchFor, vbTextCompare)
    If pos = 0 Then Exit Function

    pos = pos + Len(searchFor)
    Do While pos <= Len(obj)
        ch = Mid(obj, pos, 1)
        If ch <> " " And ch <> Chr(9) Then Exit Do
        pos = pos + 1
    Loop

    endPos = pos
    Do While endPos <= Len(obj)
        ch = Mid(obj, endPos, 1)
        If ch = "," Or ch = "}" Or ch = "]" Or _
           ch = " " Or ch = Chr(10) Or ch = Chr(13) Then Exit Do
        endPos = endPos + 1
    Loop

    JSONNumberValue = Trim(Mid(obj, pos, endPos - pos))
End Function

' Convert a column number to its Excel letter: 1→A, 12→L, 27→AA
Private Function ColLetter(col As Integer) As String
    Dim result As String
    Dim n      As Integer
    Dim r      As Integer
    result = ""
    n = col
    Do While n > 0
        r = (n - 1) Mod 26
        result = Chr(65 + r) & result
        n = (n - 1) \ 26
    Loop
    ColLetter = result
End Function
