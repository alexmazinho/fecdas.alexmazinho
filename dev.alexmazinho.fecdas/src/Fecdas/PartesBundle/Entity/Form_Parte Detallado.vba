Option Compare Database   'Usar orden de base de datos en comparaciones de cadenas
Dim estoy As String

Private idpartedetalledelete As Long 'Alex
Private dnidelete As String 'Alex
Private mbWasNewRecord As Boolean 'Alex

Private Sub Form_Delete(Cancel As Integer)
' Alex. Recull id de parte a esborrar
    idpartedetalledelete = Me.idPartedet.Value 'Alex guardo id per esborrar m_llicencies
    dnidelete = Me.dniPDet.Value ' Alex guardo DNI per actualitzar fechalicencia
End Sub

Private Sub Form_AfterDelConfirm(Status As Integer)
On Error GoTo Errordb
' Alex. Esborra registre taula
    Dim errorstr As String, queryabm As String
    Dim id As Variant
    
    queryabm = "idpartedetall_access = " & idpartedetalledelete
    If Status = acDeleteOK Then
        id = DLookup("id", "m_llicencies", queryabm)
        
        If IsNull(id) Then
            GoTo Errordb
        Else
            queryabm = "UPDATE m_llicencies SET " _
                     & " databaixa = #" & Now() & "#" _
                     & " WHERE id = " & id
            CurrentDb.Execute queryabm, dbFailOnError
            
            queryabm = "UPDATE [datos personales] SET " _
                     & " fechalicencia = NULL " _
                     & " WHERE dni = '" & dnidelete & "'"
            CurrentDb.Execute queryabm, dbFailOnError
            
        End If
    
    End If
    
    idpartedetalledelete = 0

Exit_Form_AfterDelConfirm:
    Exit Sub

Errordb:
    idpartedetalledelete = 0
    errorstr = "Error esborrat parte access -> web"
    logerror_db Err.Number, Err.Description, errorstr, "Form_Parte Detallado", "Form_AfterDelConfirm", queryabm

End Sub

Private Sub Form_AfterInsert()
On Error GoTo Errordb
' Alex. Insereix registre taula
' S'executa afterupdate abans a vegades
    Dim errorstr As String, queryabm As String
    Dim idparte_access As String
    Dim id As Variant
    Dim persona As Variant
    Dim parte As Variant
    Dim categoria As Variant
    Dim datacaducitat As Date

    queryabm = "idpartedetall_access = " & Me.idPartedet.Value
    id = DLookup("id", "m_llicencies", queryabm)
    
    If Not IsNull(id) Then
        GoTo Errordb
    Else
        ' get persona id
        queryabm = "dni = '" & Me.dniPDet.Value & "'"
        persona = DLookup("id", "m_persones", queryabm)
        If IsNull(persona) Then GoTo Errordb
    
        ' Cas estrany comportament. Alta llicència abans de parte??
        ' Per exemple posar dades client abans de introduir dades al parte
        If IsNull(Me.id_de_parte) Then
            queryabm = "El id de parte és NULL"
             GoTo Errordb
             Exit Sub
        End If
        idparte_access = Mid(StringFromGUID(Me.id_de_parte.Value), 7, 38)
        queryabm = "idparte_access = '" & idparte_access & "'"
        parte = DLookup("id", "m_partes", queryabm)
        If IsNull(parte) Then GoTo Errordb
    
        ' get codi sortida (categoria)
        queryabm = "IdCategoria = '" & Me.categoriaParteDet.Value & "' AND "
        queryabm = queryabm & "tipoparte = " & Forms![parte]![pafed]
        categoria = DLookup("códigosalidacategoria", "categorias", queryabm)
        If IsNull(categoria) Then GoTo Errordb
    
        datacaducitat = obtenirDataCaducitat
   
        queryabm = "INSERT INTO m_llicencies (" _
                 & "persona, parte, categoria, idparte_access, idpartedetall_access, " _
                 & "pesca, escafandrisme, natacio, orientacio, biologia, fotocine, " _
                 & "hockey, fotosubapnea, videosub, apnea, nocmas, fusell, " _
                 & "datacaducitat, dataentrada, datamodificacio) VALUES ("
                 
        queryabm = queryabm & persona & ", "
        queryabm = queryabm & parte & ", "
        queryabm = queryabm & categoria & ", "
        queryabm = queryabm & "'" & idparte_access & "', "
        queryabm = queryabm & Me.idPartedet.Value & ", "
        queryabm = queryabm & IIf(Me.PescaPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.EscafandrismoPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.NataciónPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.OrientaciónPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.BiologíaPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.Foto_cinePDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.hockeyPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.FSADP = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.VideoSubPDeT = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.APDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & IIf(Me.NoCMASPDet = vbTrue, 1, 0) & ", "
        'queryabm = queryabm & IIf(Me.F = vbTrue, 1, 0) & ", "
        queryabm = queryabm & esFusell & ", "
        queryabm = queryabm & "#" & Format(datacaducitat, "yyyy-mm-dd") & "#, "
        queryabm = queryabm & "#" & Now() & "#, "
        queryabm = queryabm & "#" & Now() & "#)"
        
        CurrentDb.Execute queryabm, dbFailOnError
        
        ' Actualitzar Club de la Persona. Assegurar que pertany al club per veure ok al web
        queryabm = "UPDATE m_persones SET "
        queryabm = queryabm & " club = '" & Forms![parte]![fedeclub] & "' "
        queryabm = queryabm & " WHERE id = " & persona
        
        CurrentDb.Execute queryabm, dbFailOnError
        
    End If
    
Exit_Form_AfterInsert:
    Exit Sub

Errordb:
    errorstr = "Error inserint parte access -> web"
    logerror_db Err.Number, Err.Description, errorstr, "Form_Parte Detallado", "Form_AfterInsert", queryabm

End Sub


Private Sub Form_AfterUpdate()
On Error GoTo Errordb
' Alex. Canvia registre taula
    Dim errorstr As String, queryabm As String, querycomarca As String
    Dim idparte_access As String
    Dim id As Variant
    Dim persona As Variant
    Dim parte As Variant
    Dim categoria As Variant
    Dim datacaducitat As Date

    queryabm = "idpartedetall_access = " & Me.idPartedet.Value
    id = DLookup("id", "m_llicencies", queryabm)
    
    If IsNull(id) Then
        If mbWasNewRecord = False Then GoTo Errordb
    Else
        ' get persona id
        queryabm = "dni = '" & Me.dniPDet.Value & "'"
        persona = DLookup("id", "m_persones", queryabm)
        If IsNull(persona) Then GoTo Errordb
    
        ' get parte id
        idparte_access = Mid(StringFromGUID(Me.id_de_parte.Value), 7, 38)
        queryabm = "idparte_access = '" & idparte_access & "'"
        parte = DLookup("id", "m_partes", queryabm)
        If IsNull(parte) Then GoTo Errordb
    
        ' get codi sortida (categoria)
        queryabm = "IdCategoria = '" & Me.categoriaParteDet.Value & "' AND "
        queryabm = queryabm & "tipoparte = " & Forms![parte]![pafed]
        categoria = DLookup("códigosalidacategoria", "categorias", queryabm)
        If IsNull(categoria) Then GoTo Errordb
    
        datacaducitat = obtenirDataCaducitat
    
        queryabm = "UPDATE m_llicencies SET "
        queryabm = queryabm & "persona = " & persona & ", "
        queryabm = queryabm & "parte = " & parte & ", "
        queryabm = queryabm & "categoria = " & categoria & ", "
        queryabm = queryabm & "idparte_access = '" & idparte_access & "', "
        queryabm = queryabm & "idpartedetall_access = " & Me.idPartedet.Value & ", "
        queryabm = queryabm & "pesca = " & IIf(Me.PescaPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "escafandrisme = " & IIf(Me.EscafandrismoPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "natacio = " & IIf(Me.NataciónPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "orientacio = " & IIf(Me.OrientaciónPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "biologia = " & IIf(Me.BiologíaPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "fotocine = " & IIf(Me.Foto_cinePDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "hockey = " & IIf(Me.hockeyPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "fotosubapnea = " & IIf(Me.FSAPDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "videosub = " & IIf(Me.VideoSubPDeT = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "apnea = " & IIf(Me.APDet = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "nocmas = " & IIf(Me.NoCMASPDet = vbTrue, 1, 0) & ", "
        'queryabm = queryabm & "fusell = " & IIf(Me.F = vbTrue, 1, 0) & ", "
        queryabm = queryabm & "fusell = " & esFusell & ", "
        
        queryabm = queryabm & "datacaducitat = #" & Format(datacaducitat, "yyyy-mm-dd") & "# "
        queryabm = queryabm & " WHERE id = " & id
        
        'Modificació dades llicència
        CurrentDb.Execute queryabm, dbFailOnError
                
        queryabm = "UPDATE m_persones SET "
        queryabm = queryabm & " nom = '" & Replace(Me.nom.Value, "'", "''") & "', "
        queryabm = queryabm & " cognoms = '" & Replace(Me.cognoms.Value, "'", "''") & "', "
        queryabm = queryabm & " datanaixement = #" & Format(Me.dn.Value, "yyyy-mm-dd") & "#, "
        queryabm = queryabm & " sexe = '" & Me.sexo.Value & "', "
        'queryabm = queryabm & " telefon1 = '" & ?? & "', " Pot ser null
        'queryabm = queryabm & " telefon2 = '" & ?? & "', " Pot ser null
        'queryabm = queryabm & " mail = '" & ?? & "', " Pot ser null
        If IsNull(Me.dir) Then
            queryabm = queryabm & " addradreca = '', "
        Else
            queryabm = queryabm & " addradreca = '" & Replace(Me.dir.Value, "'", "''") & "', "
        End If
        If IsNull(Me.pob) Then
            queryabm = queryabm & " addrpob = '', "
        Else
            queryabm = queryabm & " addrpob = '" & Replace(Me.pob.Value, "'", "''") & "', "
        End If
        If IsNull(Me.cp) Then
            queryabm = queryabm & " addrcp = '', "
        Else
            queryabm = queryabm & " addrcp = '" & Me.cp.Value & "', "
        End If
        If IsNull(Me.prov) Then
            queryabm = queryabm & " addrprovincia = '', "
        Else
            queryabm = queryabm & " addrprovincia = '" & StrConv(Me.prov.Value, VbStrConv.vbProperCase) & "', "
        End If
        
        If (Me.comarcadp = 0) Or (IsNull(Me.comarcadp)) Then
            queryabm = queryabm & " addrcomarca = NULL, "
        Else
         ' get nom comarca
            querycomarca = "idcomarca = " & Me.comarcadp.Value & ""
            comarca = DLookup("Cormarca", "comarca", querycomarca)
            If IsNull(comarca) Then
                queryabm = queryabm & " addrcomarca = NULL, "
            Else
                queryabm = queryabm & " addrcomarca = '" & Replace(StrConv(comarca, VbStrConv.vbProperCase), "'", "''") & "', "
            End If
        End If
        queryabm = queryabm & " club = '" & Forms![parte]![fedeclub] & "', "
        queryabm = queryabm & " addrnacionalitat = '" & Me.nacionalidad.Value & "' "
        queryabm = queryabm & " WHERE id = " & persona
        
        ' Modificació dades personals
        CurrentDb.Execute queryabm, dbFailOnError
        
    End If
    
Exit_Form_AfterUpdate:
    Exit Sub

Errordb:
    errorstr = "Error modificant parte access -> web"
    logerror_db Err.Number, Err.Description, errorstr, "Form_Parte Detallado", "Form_AfterUpdate", queryabm

End Sub

Private Function esFusell()
'Alex retorna si es fusell o no
    If (Forms![parte]![pafed] = 8) Then
        esFusell = 1
    Else
        esFusell = 0
    End If
End Function

Private Function obtenirDataCaducitat()
' Alex  calcul caducitat
    Dim datacaducitat As Date
    
    'If IsNull(Me.DataCaducitatPDET) Then
    '    If Me.[365] = True Then
    '        datacaducitat = Forms![parte]![fecha] + 365
    '    Else
    '        datacaducitat = DateSerial(Year(Forms![parte]![fecha]), 12, 31)
    '    End If
    'Else
    '    datacaducitat = Me.DataCaducitatPDET
    'End If
        
    ' Calcul igual web
    If (Forms![parte]![pafed] = 2 Or Forms![parte]![pafed] = 7 Or Forms![parte]![pafed] = 8) Then
        '365
        datacaducitat = Forms![parte]![fecha] + 365
    Else
        'Fin d'any
        datacaducitat = DateSerial(Year(Forms![parte]![fecha]), 12, 31)
    End If
    obtenirDataCaducitat = datacaducitat
    
End Function

Private Sub APDet_GotFocus()
Etiqueta265.BackColor = RGB(255, 255, 0): Etiqueta264.BackColor = 13421619
End Sub
Private Sub APDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub APDet_LostFocus()
Etiqueta265.BackColor = 13421619
End Sub
Private Sub BiologíaPDet_GotFocus()
Etiqueta255.BackColor = RGB(255, 255, 0): Etiqueta254.BackColor = 13421619
End Sub
Private Sub BiologíaPDet_LostFocus()
Etiqueta255.BackColor = 13421619
End Sub
Private Sub categoriaParteDet_AfterUpdate()
' aqui posem la marca del 365 dies
If Forms![parte]![pafed] = 7 Then Me![365] = -1
End Sub

Private Sub categoriaParteDet_GotFocus()
If Forms![parte]![pafed] = 2 Then
Me!categoriaParteDet.Locked = False
Me!categoriaParteDet = "A"
Else
Me!categoriaParteDet.Locked = False
End If

If IsNull(categoriaParteDet) Then categoriaParteDet = DLookup("[categoria]", "Datos personales", "[dni] like '" & Me![DNI] & "'")
End Sub
Private Sub categoriaParteDet_LostFocus()
Me![fechalicencia] = Forms![parte]![fecha] ' aqui poso la data a dades personals perque es controla en cursos  si el instructor te llicencia
'[fechalicencia]  també s'utilitza per controlar que no es facin llicències duplicades

If Me![categoriaParteDet] Like "d" Then Forms![parte]![directivos] = Forms![parte]![directivos] + 1
If Me![categoriaParteDet] Like "t" Then Forms![parte]![técnicos] = Forms![parte]![técnicos] + 1
If Me![categoriaParteDet] Like "s" Then Forms![parte]![seniors] = Forms![parte]![seniors] + 1
If Me![categoriaParteDet] Like "j" Then Forms![parte]![juveniles] = Forms![parte]![juveniles] + 1
If Me![categoriaParteDet] Like "i" Then Forms![parte]![infantiles] = Forms![parte]![infantiles] + 1
If Me![categoriaParteDet] Like "e" Then Forms![parte]![extranjeros] = Forms![parte]![extranjeros] + 1
If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 1 Then Forms![parte]![aficionados] = Forms![parte]![aficionados] + 1
If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 3 Then Forms![parte]![aficionados] = Forms![parte]![aficionados] + 1
If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 4 Then Forms![parte]![aficionados] = Forms![parte]![aficionados] + 1
If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 5 Then Forms![parte]![aficionados] = Forms![parte]![aficionados] + 1


If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 8 Then Forms![parte]![aficionados] = Forms![parte]![aficionados] + 1
If Me![categoriaParteDet] Like "t" And Forms![parte]![pafed] = 8 Then Forms![parte]![aficionados] = Forms![parte]![técnicos] + 1
If Me![categoriaParteDet] Like "i" And Forms![parte]![pafed] = 8 Then Forms![parte]![aficionados] = Forms![parte]![infantiles] + 1


If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 7 Then Forms![parte]![aficionados] = Forms![parte]![aficionados] + 1


If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 2 Then Forms![parte]![AficionadosNF] = Forms![parte]![AficionadosNF] + 1
If Me![categoriaParteDet] Like "a" And Forms![parte]![pafed] = 6 Then Forms![parte]![AficionadosNF] = Forms![parte]![AficionadosNF] + 1


Dim llic As Variant, Dapart As String, Respuesta As String
If Not (categoriaParteDet Like "t") Then
    llic = DLookup("DniInstructores", "instructores", "[DniInstructores] like '" & Me![DNI] & "'")
    If Not (IsNull((llic))) Then Respuesta = MsgBox("Este individuo es instructor" & (Chr(13)) & (Chr(10)) & (Chr(10)) & "Se le ha federado como NO Tecnico", 64)
End If


DniEnUs = Me![DNI]
DniNouFed = DNI
Vincde = Me.Name
If ControlDuplicados = -1 Then
    llic = DLookup("fechalicencia", "datos personales", "[dni] like " & Me![DNI])
    If Year(llic) = Year(Date) Then
        Respuesta = MsgBox("se ha encontrado una licencia de este afiliado en " & (Chr(13)) & (Chr(10)) & (Chr(10)) & "la fecha: " & llic & " en el club " & DLookup("fedeclubDP", "datos personales", "[dni] like " & Me![DNI]), 64)
    End If
End If

Exit Sub
End Sub

Private Sub cognoms_GotFocus()
If Not (IsNull([cognoms])) Then If IsNull([dn]) Then MsgBox "falta fecha de nacimiento"
End Sub

Private Sub cognoms_KeyPress(KeyAscii As Integer)
If KeyAscii = 95 Then DoCmd.OpenForm "Datos Personales", , , "[dni]='" & Me![DNI] & "'"
End Sub

Private Sub Comando266_Click()
Dim Respuesta As String
If Disco Like "si" Then
    Respuesta = MsgBox("Se inicia proceso normal de funcionamiento", 49)
    If Respuesta = vbOK Then Disco = "no"
    If Respuesta = vbCancel Then Disco = "si": MsgBox "Cambio anulado" & (Chr(13)) & (Chr(10)) & (Chr(10)) & "Se usará la captura de datos desde archivos"
    Exit Sub
Else
    Respuesta = MsgBox("Se inicia opciones para captura de datos desde archivos", 17)
    If Respuesta = vbOK Then Disco = "si"
    If Respuesta = vbCancel Then Disco = "no": MsgBox "Cambio anulado" & (Chr(13)) & (Chr(10)) & (Chr(10)) & "Se usará el proceso de introducción manual"
    Exit Sub
End If

If Disco Like "si" Then
    Me![nom].TabStop = True: Me![dn].TabStop = True: Me![sexo].TabStop = True
    Me![prov].TabStop = True: Me![nacionalidad].TabStop = True
Else
    Me![nom].TabStop = False: Me![dn].TabStop = False: Me![sexo].TabStop = False
    Me![prov].TabStop = False: Me![nacionalidad].TabStop = False
End If
End Sub
Private Sub comarcadp_KeyPress(KeyAscii As Integer)
If KeyAscii = 95 Then DoCmd.OpenForm "datoscp", , , "[CpMunicipio]='" & Me![cp] & "'"
End Sub
Private Sub cp_AfterUpdate()
NouMunicipi = 0
If Disco Like "si" Then Exit Sub
If IsNull(Me![cp]) Then Exit Sub
[prov] = DLookup("[prov]", "[provincias]", "[cp]='" & Left$(Me![cp], 2) & "'")
[pob] = DLookup("[MUNICIPI]", "[municipios]", "[CpMunicipio]='" & Me![cp] & "'")
If IsNull(Me![pob]) Then NouMunicipi = -1: Me![pob].SetFocus
End Sub
Private Sub cp_KeyPress(KeyAscii As Integer)
ProcedoDe = Me.Name
If KeyAscii = 95 Then DoCmd.OpenForm "datoscp", , , "[CpMunicipio]='" & Me![cp] & "'"
End Sub

Private Sub Ctl365_AfterUpdate()
Me![FL365] = Me![365]
End Sub

Private Sub dir_Change()
Me![cartas] = -1
End Sub
Private Sub cognoms_AfterUpdate()
CognomNouFed = cognoms
CognomsEnUs = Me![cognoms]
If Disco Like "si" Then GoTo koko
ProcedoDe = Me.Name
DoCmd.OpenForm "BuscadorPorApellidos", , , "[cognoms] Like '" & Me![cognoms] & "*'"
Me.Undo
Me![cognoms].SetFocus
koko:
End Sub

Private Sub dir_LostFocus()
Me![DataCaducitatPDET] = Forms![parte]![pafed].Column(2)
If Me![DataCaducitatPDET] = #1/1/2007# Then Me![DataCaducitatPDET] = Forms![parte]![fecha] + 365
End Sub

Private Sub DNI_AfterUpdate()
On Error GoTo kk
Dim Mu As Recordset
DniEnUs = Me![DNI]
DniNouFed = DNI
Vincde = Me.Name
Dim llic As Variant, Dapart As String, Respuesta As String
Set Mu = CurrentDb.OpenRecordset("SELECT [parte detallado].dniPDet, [parte detallado].fedeclubParteDet,[parte detallado].fechaParteDet FROM [parte detallado] WHERE ((([parte detallado].dniPDet) Like '" & Me![DNI] & "')) ORDER BY [parte detallado].fechaParteDet DESC;")
If Year(Mu!fechaParteDet) = Year(Date) Then
    Respuesta = MsgBox("se ha encontrado una licencia de este afiliado en " & (Chr(13)) & (Chr(10)) & (Chr(10)) & "la fecha: " & Mu!fechaParteDet & " en el club " & Mu![fedeclubParteDet], 64)
End If
Mu.Close
Exit Sub
kk:
MsgBox "No hi ha dades amb aquest DNI, introdueix el Cognom"
Exit Sub

End Sub
Private Sub dni_BeforeUpdate(Cancel As Integer)
On Error GoTo kk
Dim contador As Single, trobat As Variant

If Disco Like "si" Then
       trobat = DLookup("dni", "datos personales", "[dni] like '" & Me![DNI] & "'")
        If IsNull(trobat) Then
            Dim Mu As Recordset
            
            Set Mu = CurrentDb.OpenRecordset("datos personales")
            Mu.AddNew
            Mu!DNI = Me![DNI]
            Mu.Update
            Mu.Close
        End If
End If
Exit Sub
kk:
Resume Next
Exit Sub
End Sub

Private Sub dni_KeyPress(KeyAscii As Integer)
If KeyAscii = 95 Then DoCmd.OpenForm "Datos Personales", , , "[dni]='" & Me![DNI] & "'"
End Sub

Private Sub EscafandrismoPDet_GotFocus()
Etiqueta252.BackColor = RGB(255, 255, 0)
'Etiqueta251.BackColor = 13421619
End Sub
Private Sub EscafandrismoPDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub EscafandrismoPDet_LostFocus()
Etiqueta252.BackColor = 13421619
End Sub
Private Sub Form_BeforeUpdate(Cancel As Integer)

    mbWasNewRecord = Me.NewRecord 'Alex

    If IsNull(Me.categoriaParteDet) Or (Me.categoriaParteDet = 0) Then
        ' Alex. Evitar llicència sense categoria
        Cancel = True
        MsgBox "Cal indicar una categoria"
        Me.categoriaParteDet.SetFocus
        Exit Sub
        'Me.Undo
    End If

Me![Biología] = Me![BiologíaPDet]: Me![Escafandrismo] = Me![EscafandrismoPDet]
Me![num lic] = Me![LicenciaPDet] & "_" & Year(PaFe): Me![Orientación] = Me![OrientaciónPDet]
Me![Pesca] = Me![PescaPDet]: Me![Natación] = Me![NataciónPDet]: Me![JA] = Me![JAPDet]
Me![Foto-cine] = Me![Foto-cinePDet]: Me![hockey] = Me![hockeyPDet]: Me![NoCMASdp] = Me![NoCMASPDet]
Me![FSADP] = Me![FSAPDet]: Me![ADP] = Me![APDet]
Me![fedeclubDP] = Forms![parte]![fedeclubParte]: Me![fedeclubParteDet] = Forms![parte]![fedeclubParte]
Me![fechalicencia] = Forms![parte]![fecha de altaParte]: Me![fechaParteDet] = Forms![parte]![fecha de altaParte]
Me![categoria] = Me![categoriaParteDet]: Me![cartas] = -1
End Sub
Private Sub Comando83_Click()
On Error GoTo Err_Comando83_Click
    DoCmd.GoToRecord , , acFirst
Exit_Comando83_Click:
    Exit Sub
Err_Comando83_Click:
    MsgBox Err.Description
    Resume Exit_Comando83_Click
End Sub
Private Sub Comando84_Click()
On Error GoTo Err_Comando84_Click
    DoCmd.GoToRecord , , acPrevious
Exit_Comando84_Click:
    Exit Sub
Err_Comando84_Click:
    MsgBox Err.Description
    Resume Exit_Comando84_Click
End Sub
Private Sub Comando85_Click()
On Error GoTo Err_Comando85_Click
    DoCmd.GoToRecord , , acNext
Exit_Comando85_Click:
    Exit Sub
Err_Comando85_Click:
    MsgBox Err.Description
    Resume Exit_Comando85_Click
End Sub
Private Sub Comando86_Click()
On Error GoTo Err_Comando86_Click
    DoCmd.GoToRecord , , acLast
Exit_Comando86_Click:
    Exit Sub
Err_Comando86_Click:
    MsgBox Err.Description
    Resume Exit_Comando86_Click
End Sub
Private Sub Comando87_Click()
On Error GoTo Err_Comando87_Click
    DoCmd.GoToRecord , , acNewRec
    Me![DNI].SetFocus
Exit_Comando87_Click:
    Exit Sub
Err_Comando87_Click:
    MsgBox Err.Description
    Resume Exit_Comando87_Click
End Sub
Private Sub Comando88_Click()
   Dim Respuesta As String
    Respuesta = MsgBox("Aquesta acció elimina aquesta llicència federativa i corregeix el apunt de última llicència a pantalla dades personals" & (Chr(13)) & (Chr(10)) & (Chr(10)) & "¿Realmente desea Hacerlo?", 52)
    If Respuesta = vbNo Then Exit Sub

        Dim de As Recordset
        
        Set de = CurrentDb.OpenRecordset("SELECT [datos personales].DNI, [datos personales].fechalicencia, [datos personales].FL365, [parte detallado].fechaParteDet, [parte detallado].[365] FROM [datos personales] INNER JOIN [parte detallado] ON [datos personales].DNI = [parte detallado].dniPDet WHERE ((([datos personales].DNI) like '" & Me![DNI] & "' )) ORDER BY [parte detallado].fechaParteDet DESC;")
        de.MoveNext
        de.MoveFirst 'Alex
        de.Edit
        de![FL365] = de![365]
        de![fechalicencia] = de![fechaParteDet]
        de.Update
        de.Close
        
        
On Error GoTo Err_Comando88_Click
    DoCmd.DoMenuItem acFormBar, acEditMenu, acDelete, , acMenuVer70  'Alex afegit
    'DoCmd.DoMenuItem acFormBar, acEditMenu, 8, , acMenuVer70 ' Alex comentat
    'DoCmd.DoMenuItem acFormBar, acEditMenu, 6, , acMenuVer70 ' Alex comentat, no entenc preguntar Xavi
Exit_Comando88_Click:
    Exit Sub
Err_Comando88_Click:
Resume Next
End Sub

Private Sub Form_Current()
Me![DNI].SetFocus
Me![APDet].Visible = False: Me![Foto-cinePDet].Visible = False: Me![FSAPDet].Visible = False: Me![EscafandrismoPDet].Visible = False
Me![PescaPDet].Visible = False: Me![OrientaciónPDet].Visible = False: Me![NataciónPDet].Visible = False: Me![hockeyPDet].Visible = False
Me![F].Visible = False: Me![365].Visible = False: Me![NoCMASPDet].Visible = False: Me![VideoSubPDeT].Visible = False

If Forms![parte]![pafed] = 2 Then Me![NoCMASPDet].Visible = True: GoTo Kokoloko
If Forms![parte]![pafed] = 6 Then Me![NoCMASPDet].Visible = True: GoTo Kokoloko
If Forms![parte]![pafed] = 7 Then Me![365].Visible = True: GoTo Kokoloko
If Forms![parte]![pafed] = 8 Then Me![F].Visible = True: Me![365].Visible = True: GoTo Kokoloko


Me![APDet].Visible = True: Me![Foto-cinePDet].Visible = True: Me![FSAPDet].Visible = True
Me![EscafandrismoPDet].Visible = True
Me![PescaPDet].Visible = True: Me![OrientaciónPDet].Visible = True: Me![NataciónPDet].Visible = True
Me![hockeyPDet].Visible = True
Me![VideoSubPDeT].Visible = True

Kokoloko:
End Sub

Private Sub Form_GotFocus()
If IsNull([DNI]) Then Me![DNI].SetFocus
End Sub

Private Sub Foto_cinePDet_GotFocus()
Etiqueta257.BackColor = RGB(255, 255, 0): Etiqueta259.BackColor = 13421619
End Sub
Private Sub Foto_cinePDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub Foto_cinePDet_LostFocus()
Etiqueta257.BackColor = 13421619
End Sub
Private Sub FSAPDet_GotFocus()
Etiqueta264.BackColor = RGB(255, 255, 0): Etiqueta257.BackColor = 13421619
End Sub
Private Sub FSAPDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub FSAPDet_LostFocus()
Etiqueta264.BackColor = 13421619
End Sub
Private Sub hockeyPDet_GotFocus()
Etiqueta259.BackColor = RGB(255, 255, 0): Etiqueta262.BackColor = 13421619
End Sub
Private Sub hockeyPDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub hockeyPDet_LostFocus()
Etiqueta259.BackColor = 13421619
End Sub
Private Sub JAPDet_GotFocus()
Etiqueta256.BackColor = RGB(255, 255, 0): Etiqueta255.BackColor = 13421619
End Sub
Private Sub JAPDet_LostFocus()
Etiqueta256.BackColor = 13421619
End Sub
Private Sub nacionalidad_KeyPress(KeyAscii As Integer)
If KeyAscii = 95 Then DoCmd.OpenTable "Nacionalidades"
End Sub
Private Sub NataciónPDet_GotFocus()
Etiqueta253.BackColor = RGB(255, 255, 0): Etiqueta251.BackColor = 13421619
End Sub
Private Sub NataciónPDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub NataciónPDet_LostFocus()
Etiqueta253.BackColor = 13421619
End Sub

Private Sub NoCMASPDet_AfterUpdate()
If [categoriaParteDet] Like "T" Then
MsgBox "un NC no puede ser inscrito como Técnico"
NoCMASPDet = 0
End If
End Sub

Private Sub NoCMASPDet_GotFocus()
Etiqueta252.BackColor = 13421619: Etiqueta262.BackColor = RGB(255, 255, 0)
If Forms![parte]![pafed] = 2 Then NoCMASPDet = -1
End Sub
Private Sub NoCMASPDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub NoCMASPDet_LostFocus()
Etiqueta262.BackColor = 13421619
End Sub
Private Sub OrientaciónPDet_GotFocus()
Etiqueta254.BackColor = RGB(255, 255, 0): Etiqueta253.BackColor = 13421619
End Sub
Private Sub OrientaciónPDet_LostFocus()
Etiqueta254.BackColor = 13421619
End Sub
Private Sub PescaPDet_GotFocus()
Etiqueta251.BackColor = RGB(255, 255, 0): Etiqueta265.BackColor = 13421619
End Sub
Private Sub PescaPDet_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub
Private Sub PescaPDet_LostFocus()
Etiqueta251.BackColor = 13421619
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
ProcedoDe = "PARTE detallado"
If KeyAscii = 95 Then DoCmd.OpenForm "datoscp", , , "[CpMunicipio]='" & Me![cp] & "'"
End Sub

Private Sub VideoSubPDeT_GotFocus()
Etiqueta273.BackColor = RGB(255, 255, 0): Etiqueta273.BackColor = 13421619
End Sub

Private Sub VideoSubPDeT_KeyPress(KeyAscii As Integer)
If Not (KeyAscii = 32) And Not (KeyAscii = 13) Then Me![DNI].SetFocus: DoCmd.GoToRecord , , acNext
End Sub

Private Sub VideoSubPDeT_LostFocus()
Etiqueta273.BackColor = 13421619
End Sub

Private Sub Comando279_Click()
On Error GoTo Err_Comando279_Click


    DoCmd.DoMenuItem acFormBar, acEditMenu, 8, , acMenuVer70
    DoCmd.DoMenuItem acFormBar, acEditMenu, 6, , acMenuVer70

Exit_Comando279_Click:
    Exit Sub

Err_Comando279_Click:
    MsgBox Err.Description
    Resume Exit_Comando279_Click
    
End Sub
