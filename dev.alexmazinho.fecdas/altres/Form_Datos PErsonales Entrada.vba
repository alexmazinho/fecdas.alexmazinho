Option Compare Database   'Usar orden de base de datos en comparaciones de cadenas

Private Sub Form_AfterInsert()
On Error GoTo Errordb
' Alex. Insereix registre taula
' S'executa afterupdate abans a vegades
    Dim errorstr As String, queryabm As String, querycomarca As String
    Dim idparte_access As String
    Dim id As Variant
    Dim parte As Variant
    Dim categoria As Variant
    Dim comarca As Variant
    Dim datacaducitat As Date


    ' get persona id
    queryabm = "dni = '" & Me.DNI & "'"
    id = DLookup("id", "m_persones", queryabm)
    
    If Not IsNull(id) Then
        GoTo Errordb
    Else
        queryabm = "INSERT INTO m_persones (nom, cognoms, dni, datanaixement, sexe, " _
                 & "telefon1, telefon2, mail, addradreca, addrpob, addrcp, addrprovincia, " _
                 & "addrcomarca, addrnacionalitat, club, dataentrada, datamodificacio) VALUES (" _
        
        queryabm = queryabm & "'" & Replace(Me.nom.Value, "'", "''") & "', "
        queryabm = queryabm & "'" & Replace(Me.cognoms.Value, "'", "''") & "', "
        queryabm = queryabm & "'" & Me.DNI.Value & "', "
        queryabm = queryabm & "#" & Format(Me.dn.Value, "yyyy-mm-dd") & "#, "
        queryabm = queryabm & "'" & Me.sexo.Value & "', "
        If IsNull(Me.telf) Then
            queryabm = queryabm & "NULL, "
        Else
            queryabm = queryabm & "'" & Me.telf.Value & "', "
        End If
        queryabm = queryabm & "NULL, "  ' En aquest form no existeix telefon2
        queryabm = queryabm & "NULL, "  ' En aquest form no existeix mail
        If IsNull(Me.dir) Then
            queryabm = queryabm & " '', "
        Else
            queryabm = queryabm & "'" & Replace(Me.dir.Value, "'", "''") & "', "
        End If
        If IsNull(Me.pob) Then
            queryabm = queryabm & " '', "
        Else
            queryabm = queryabm & "'" & Replace(Me.pob.Value, "'", "''") & "', "
        End If
        If IsNull(Me.cp) Then
            queryabm = queryabm & " '', "
        Else
            queryabm = queryabm & "'" & Me.cp.Value & "', "
        End If
        If IsNull(Me.prov) Then
            queryabm = queryabm & " '', "
        Else
            queryabm = queryabm & "'" & StrConv(Me.prov.Value, VbStrConv.vbProperCase) & "', "
        End If
        If (Me.comarcadp = 0) Or (IsNull(Me.comarcadp)) Then
            queryabm = queryabm & "NULL, "
        Else
            ' get nom comarca
            querycomarca = "idcomarca = " & Me.comarcadp.Value & ""
            comarca = DLookup("Cormarca", "comarca", querycomarca)
            If IsNull(comarca) Then
                queryabm = queryabm & "NULL, "
            Else
                queryabm = queryabm & "'" & Replace(StrConv(comarca, VbStrConv.vbProperCase), "'", "''") & "', "
            End If
        End If
        queryabm = queryabm & "'" & Me.nacionalidad.Value & "', "
        queryabm = queryabm & "'CAT999', "  ' En alta poso CAT999, si després afegeixen llicència
        queryabm = queryabm & "#" & Now() & "#, "
        queryabm = queryabm & "#" & Now() & "#)"
       
        CurrentDb.Execute queryabm, dbFailOnError
        
    End If
    
Exit_Form_AfterInsert:
    Exit Sub

Errordb:
    errorstr = "Error inserint dades personals access -> web"
    logerror_db Err.Number, Err.Description, errorstr, "Form_Datos Personales Entrada", "Form_AfterInsert", queryabm

End Sub

Private Sub Botón103_Click()
On Error GoTo Err_Botón103_Click


    DoCmd.DoMenuItem A_FORMBAR, A_EDITMENU, A_SELECTRECORD_V2, , A_MENU_VER20
    DoCmd.DoMenuItem A_FORMBAR, A_EDITMENU, A_DELETE_V2, , A_MENU_VER20

Exit_Botón103_Click:
    Exit Sub

Err_Botón103_Click:
    MsgBox error$
    Resume Exit_Botón103_Click
    
End Sub

Private Sub cognoms_AfterUpdate()
Me![cognoms] = UCase(Me![cognoms])
End Sub

Private Sub cognoms_GotFocus()
If IsNull(CognomsEnUs) Or (CognomsEnUs) Like "" Then Exit Sub
Me![cognoms] = CognomsEnUs

Me![cognoms] = UCase(Me![cognoms])
End Sub

Private Sub Comando801_GotFocus()
If IsNull([dn]) Then [dn].SetFocus
End Sub

Private Sub cp_AfterUpdate()
On Error GoTo cp_Err
NouMunicipi = 0
[pob] = DLookup("[MUNICIPI]", "[municipios]", "[CpMunicipio]='" & Me![cp] & "'")
[prov] = DLookup("[prov]", "[provincias]", "[cp]='" & Left$(Me![cp], 2) & "'")
[comarcadp] = DLookup("[ComarcaMunicipio]", "[municipios]", "[CpMunicipio]='" & Me![cp] & "'")

If IsNull(Me![pob]) Then NouMunicipi = -1: Me![pob].SetFocus
cp_Exit:
Exit Sub

cp_Err:
    MsgBox error$
    Resume cp_Exit

End Sub
Private Sub cp_KeyPress(KeyAscii As Integer)
If KeyAscii = 95 Then
ProcedoDe = Me.Name
DoCmd.OpenForm "datoscp", , , "[CpMunicipio]='" & Me![cp] & "'"
End If

End Sub

Private Sub dn_LostFocus()
If IsNull([dn]) Then MsgBox "Es imprescindible introducir la fecha"
End Sub

Private Sub DNI_AfterUpdate()
Me![DNI] = UCase(Me![DNI])
End Sub

Private Sub dni_GotFocus()
If IsNull(DniEnUs) Or (DniEnUs) Like "" Then Exit Sub
Me![DNI] = DniEnUs
End Sub

Private Sub Form_Close()
If ProcedoDe Like "PARTE detallado" Then
    Forms![parte]![parte detallado]![DNI].SetFocus
    Forms![parte]![parte detallado]![DNI] = Me![DNI]
    Exit Sub
End If

If EstáCargado("Parte") Then
    Forms![parte]![parte detallado]![DNI].SetFocus
    Forms![parte]![parte detallado]![DNI] = Me![DNI]
End If

End Sub

Private Sub Texto786_GotFocus()
DoCmd.Close
End Sub
Private Sub Comando801_Click()
On Error GoTo Err_Comando801_Click

    DoCmd.Close
Exit_Comando801_Click:
    Exit Sub
Err_Comando801_Click:
    MsgBox Err.Description
    Resume Exit_Comando801_Click
End Sub

Private Sub nom_AfterUpdate()
Me![nom] = MultiCap(Me![nom])
End Sub

Private Sub pob_AfterUpdate()
If NouMunicipi = -1 Then
        Dim Mu As Recordset
        
        Set Mu = CurrentDb.OpenRecordset("municipios")
        Mu.AddNew
        Mu!MUNICIPI = Me![pob]
        Mu!CpMunicipio = Me![cp]
        Mu.Update
        Mu.Close
End If
NouMunicipi = 0
End Sub
Private Sub pob_KeyPress(KeyAscii As Integer)

If KeyAscii = 95 Then
    ProcedoDe = Me.Name
    DoCmd.OpenForm "datoscp", , , "[CpMunicipio]='" & Me![cp] & "'"
End If
End Sub

Private Sub sexo_GotFocus()
If IsNull([dn]) Then [dn].SetFocus
End Sub
