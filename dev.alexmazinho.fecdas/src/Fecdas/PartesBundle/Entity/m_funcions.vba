Option Compare Database
Option Explicit

Public Function msglastupdate(taula As String) As Variant
    Dim actualitzacio As Variant
    Dim Response As Integer
    
    Response = MsgBox(prompt:="Des de la darrera vegada ('Si') " & vbCrLf & _
                              "o totes les dades, poc eficient ('No').", Buttons:=vbYesNoCancel)
    Select Case Response
    Case vbCancel
        actualitzacio = "Exit"
    Case vbYes
        actualitzacio = dataactualitzacio(taula)
    Case vbNo
        actualitzacio = ""
    End Select
    
    msglastupdate = actualitzacio
    
End Function


Public Function dataactualitzacio(taula As String) As Variant
    Dim actualitzacio As Variant
    
    actualitzacio = DLookup("lastupdate", "m_lastupdate", "taula = '" & taula & "'")
    
    If IsNull(actualitzacio) Then
        dataactualitzacio = ""
    Else
        dataactualitzacio = actualitzacio
    End If
End Function

Public Sub logerror_db(errnum As Long, errdesc As String, err_sms As String, form_name As String, sub_name As String, query As String)
On Error GoTo errorlog
    Dim qr As String
    
    qr = "INSERT INTO m_logerrors (num, descripcio, sms, form, " _
                          & " sub, query) VALUES ("
    qr = qr & errnum & ", "
    qr = qr & "'" & Replace(errdesc, "'", "''") & "', "
    qr = qr & "'" & Replace(err_sms, "'", "''") & "', "
    qr = qr & "'" & form_name & "', "
    qr = qr & "'" & sub_name & "', "
    
    query = Replace(query, "'", "''")
    'query = Replace(query, "#", "[#]")
    qr = qr & "'" & query & "') "  ' escape quote
    
    CurrentDb.Execute qr
    
    Exit Sub
errorlog:
    MsgBox "error log" & Len(qr)
    MsgBox "Error escrivint el registre " & qr

End Sub



