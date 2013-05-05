Option Compare Database
Option Explicit

Private idpartedelete As String 'Alex
Private mbWasNewRecord As Boolean 'Alex

Private Sub Form_Delete(Cancel As Integer)
' Alex. Recull id de parte a esborrar
    idpartedelete = StringFromGUID(Me.idParte.Value)  'Alex guardo id per esborrar m_partes
    idpartedelete = Mid(idpartedelete, 7, 38)
End Sub

Private Sub Form_AfterDelConfirm(Status As Integer)
On Error GoTo Errordb
' Alex. Esborra registre taula
    Dim errorstr As String, queryabm As String, query As String
    Dim id As Variant
    Dim ws As DAO.Workspace
    Dim db As DAO.Database
    
    Set ws = DBEngine.Workspaces(0)
    Set db = CurrentDb
    
    queryabm = "idparte_access = '" & idpartedelete & "'"
    If Status = acDeleteOK Then
        ' Canviar fechalicencia (Última) persones esborrades
        ' Pendent
        id = DLookup("id", "m_partes", queryabm)
        
        If IsNull(id) Then
            errorstr = "Parte no trobat al web"
            GoTo Errordades
        Else
            ws.BeginTrans
            
            ' Marcar parte de baixa
            queryabm = "UPDATE m_partes SET " _
                     & " databaixa = #" & Now() & "#" _
                     & " WHERE id = " & id
            db.Execute queryabm, dbFailOnError
            
            ' Marcar llicències del parte de baixa
            queryabm = "UPDATE m_llicencies SET " _
                     & " databaixa = #" & Now() & "#" _
                     & " WHERE parte = " & id
            db.Execute queryabm, dbFailOnError
            
            ws.CommitTrans
        End If
    
    End If
    
    idpartedelete = 0

Exit_Form_AfterDelConfirm:
    Exit Sub

Errordb:
    errorstr = "Error delete parte Access -> Web"
    ws.Rollback

Errordades:
    idpartedelete = 0
    logerror_db Err.Number, Err.Description, errorstr, "Form_Parte", "Form_AfterDelConfirm", queryabm

End Sub


Private Sub Form_AfterInsert()
On Error GoTo Errordb
' Alex. Insereix registre taula
' S'executa afterupdate abans a vegades
    Dim errorstr As String, queryabm As String
    Dim idparte_access As String
    Dim id As Variant

    idparte_access = Mid(StringFromGUID(Me.idParte.Value), 7, 38)
   
    queryabm = "idparte_access = '" & idparte_access & "'"
    id = DLookup("id", "m_partes", queryabm)
    
    If Not IsNull(id) Then
        GoTo Errordb
    Else
        queryabm = "INSERT INTO m_partes (" _
                 & "tipus, club, numrelacio, dataalta, dataentrada, " _
                 & "datamodificacio, datapagament, datafacturacio, idparte_access) VALUES ("
        
        queryabm = queryabm & Me.pafed.Value & ", "
        queryabm = queryabm & "'" & Me.fedeclub.Value & "', "
        queryabm = queryabm & Me.nº_de_relación.Value & ", "
        queryabm = queryabm & "#" & Format(Me.[fecha de altaParte].Value, "yyyy-mm-dd") & "#, "
        queryabm = queryabm & "#" & Now() & "#, "
        queryabm = queryabm & "#" & Now() & "#, "
        queryabm = queryabm & "NULL, NULL, "
        
        'Sempre NULL
        'If IsNull(Me.partefacturado) Then
        '    queryabm = queryabm & "NULL, NULL, "
        'Else
        '    queryabm = queryabm & "#" & Me.partefacturado.Value & "#, " & "#" & Me.partefacturado.Value & "#, "
        'End If
        queryabm = queryabm & "'" & idparte_access & "') "
        
        CurrentDb.Execute queryabm, dbFailOnError
        
    End If
    
Exit_Form_AfterInsert:
    Exit Sub

Errordb:
    errorstr = "Error inserint parte access -> web"
    logerror_db Err.Number, Err.Description, errorstr, "Form_Parte", "Form_AfterInsert", queryabm

End Sub

Private Sub Form_BeforeUpdate(Cancel As Integer)
' Alex. Marca indicar de nou registre
    mbWasNewRecord = Me.NewRecord 'alex
    
    If IsNull(Me.fedeclub) Or (Me.fedeclub = 0) Then
    ' Alex. Evitar Insert sense club
        Cancel = True
        MsgBox "Cal indicar un Club"
        Me.fedeclub.SetFocus
        Exit Sub
    End If

    If IsNull(Me.pafed) Or (Me.pafed = 0) Then
    ' Alex. Evitar Insert sense Tipus parte
        Cancel = True
        MsgBox "Cal indicar un tipus de Parte"
        Me.pafed.SetFocus
        Exit Sub
    End If

End Sub


Private Sub Form_AfterUpdate()
On Error GoTo Errordb
' Alex. Canvia registre taula
    Dim errorstr As String, queryabm As String
    Dim idparte_access As String
    Dim id As Variant

    idparte_access = Mid(StringFromGUID(Me.idParte), 7, 38)
    queryabm = "idparte_access = '" & idparte_access & "'"
    id = DLookup("id", "m_partes", queryabm)
    
    If IsNull(id) Then
        If mbWasNewRecord = False Then GoTo Errordb
    Else
        queryabm = "UPDATE m_partes SET "
        queryabm = queryabm & "tipus = " & Me.pafed.Value & ", "
        queryabm = queryabm & "club = '" & Me.fedeclub.Value & "', "
        queryabm = queryabm & "numrelacio = " & Me.nº_de_relación.Value & ", "
        queryabm = queryabm & "dataalta = #" & Format(Me.[fecha de altaParte].Value, "yyyy-mm-dd") & "#, "
        
        If IsNull(Me.partefacturado) Then
            queryabm = queryabm & "datapagament = NULL, "
            queryabm = queryabm & "datafacturacio = NULL "
        Else
        ' Cal modificar, què passa quan facturen??
            queryabm = queryabm & "datapagament = #" & Format(Me.partefacturado.Value, "yyyy-mm-dd") & "#, "
            queryabm = queryabm & "datafacturacio = #" & Format(Me.partefacturado.Value, "yyyy-mm-dd") & "# "
        End If
        queryabm = queryabm & " WHERE id = " & id
        
        'Modificació dades parte
        CurrentDb.Execute queryabm, dbFailOnError
        
    End If
    
Exit_Form_AfterUpdate:
    Exit Sub

Errordb:
    errorstr = "Error modificant parte access -> web"
    logerror_db Err.Number, Err.Description, errorstr, "Form_Parte", "Form_AfterUpdate", queryabm

End Sub

Private Sub aficionados_AfterUpdate()
ParteAficionados = Me![aficionados]
End Sub

Private Sub Comando25_Click()
On Error GoTo kk
' controla si es vol un full per club
Me!Comando85.SetFocus
Me!Comando25.Visible = False
If Not (IsNull(Me![partefacturado])) Then Exit Sub

Me![partefacturado] = Date
Dim Respuesta As Variant, mem1 As Integer, meme As Integer, Memem1 As Integer, MemoCat As String, poko As String
Respuesta = MsgBox("¿Desea rellenar factura?", 52)
    If Respuesta = vbNo Then Exit Sub

Dim FacDet As Recordset, Fac As Recordset, Cli As Recordset

Set Fac = CurrentDb.OpenRecordset("salida")
Set FacDet = CurrentDb.OpenRecordset("salida detalles")

MemCo1 = DMax("[nº factura]", "SALIDA", "right$([nº factura],4)= Forms![presentación]![Ejercicio]")


If IsNull(MemCo1) Or MemCo1 Like "" Then MemCo1 = "00000000/2013"

mem1 = Left$([MemCo1], 5): meme = mem1: Memem1 = meme + 1: MemCo1 = Memem1
If Right$(Me![fedeclub], 3) Like "998" Then MsgBox " la factura debe ser modificada con los datos del cliente"

Fac.AddNew

If Len(MemCo1) = 1 Then MemCo1 = "0000" & MemCo1 & "/" & Forms![Presentación]![Ejercicio]
If Len(MemCo1) = 2 Then MemCo1 = "000" & MemCo1 & "/" & Forms![Presentación]![Ejercicio]
If Len(MemCo1) = 3 Then MemCo1 = "00" & MemCo1 & "/" & Forms![Presentación]![Ejercicio]
If Len(MemCo1) = 4 Then MemCo1 = "0" & MemCo1 & "/" & Forms![Presentación]![Ejercicio]
If Len(MemCo1) = 5 Then MemCo1 = MemCo1 & "/" & Forms![Presentación]![Ejercicio]

Fac![Nº Factura] = MemCo1

If Me.fedeclub Like "cat998" Then
    Fac![tipocliente] = 1
Else
    Fac![tipocliente] = 3
End If

'-------------------------------
Fac![FechaPedido] = Date
Fac![Nifclientepedido] = Me![fedeclub].Column(2)
Fac![Destinatario] = Me![fedeclub].Column(1)
Fac.Update
'------------------------------------
Fac.MoveLast
If Not (IsNull(Me![directivos])) And (Me![directivos] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 'D'")
    FacDet![cantidadsalidadet] = Me![directivos]
    GoSub PeroPepe
End If
If Not (IsNull(Me![técnicos])) And (Me![técnicos] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 't' and [tipoparte] =" & Me![pafed])
    FacDet![cantidadsalidadet] = Me![técnicos]
    GoSub PeroPepe
End If
If Not (IsNull(Me![aficionados])) And (Me![aficionados] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 'a' and [tipoparte] =" & Me![pafed])
    
    FacDet![cantidadsalidadet] = Me![aficionados]
    
    GoSub PeroPepe
End If
If Not (IsNull(Me![AficionadosNF])) And (Me![AficionadosNF] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 'a' and [tipoparte] =" & Me![pafed])
    FacDet![cantidadsalidadet] = Me![AficionadosNF]
    
    GoSub PeroPepe
End If

If Not (IsNull(Me![seniors])) And (Me![seniors] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 's' and [tipoparte] =" & Me![pafed])
    FacDet![cantidadsalidadet] = Me![seniors]
    GoSub PeroPepe
End If
If Not (IsNull(Me![extranjeros])) And (Me![extranjeros] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 'e' and [tipoparte] =" & Me![pafed])
    FacDet![cantidadsalidadet] = Me![extranjeros]
    GoSub PeroPepe
End If

If Not (IsNull(Me![juveniles])) And (Me![juveniles] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 'j' and [tipoparte] =" & Me![pafed])
    FacDet![cantidadsalidadet] = Me![juveniles]
    GoSub PeroPepe
End If

If Not (IsNull(Me![infantiles])) And (Me![infantiles] <> 0) Then
    FacDet.AddNew
    MemoCat = DLookup("[códigosalidacategoria]", "categorias", " [IdCategoria] like 'i' and [tipoparte] =" & Me![pafed])
    FacDet![cantidadsalidadet] = Me![infantiles]
    GoSub PeroPepe
End If
MsgBox "Factura tramitada"
FacDet.Close
Fac.Close
fin:


Exit Sub
PeroPepe:
    FacDet![cpsalidadet] = MemoCat
    FacDet![iddesalida] = Fac![idpedido]
            
    FacDet![comentarioSalDet] = pafed & "-" & Me.[nº de relación] & "-" & Format(Me.[fecha de altaParte], "dd/mm/yy")
    If Right$(Me![fedeclub], 3) Like "998" Then
        FacDet![PrecioSalDet] = DLookup("[Precioparticularproducto]", "producto", " [cp]=" & MemoCat)
    Else
        FacDet![PrecioSalDet] = DLookup("[PrecioClubproducto]", "producto", " [cp]=" & MemoCat)
    End If
    FacDet.Update
Return
kk:

    MsgBox Err.Description
End Sub

Private Sub Comando39_Click()

'comprovo si ja s'ha fet l'envio de dades
    'if me![dddd]=-1 then MsgBox "la factura ja s'ha enviat":exit sub

'comprova si esta la carpeta de destí
Dim ruta As String, hay As Variant
ruta = DLookup("[Rutacontaplus]", "preferencias", "[Id]=1")
ruta = ruta + "ruta.txt"
hay = ExisteArchivo(ruta)
If hay = 0 Then MsgBox "no localitzo la carpeta d'ordinador comptabilitat": Exit Sub

' preparo el nom de l'arxiu
Dim kk As Variant
'4   kk = DLookup("[Rutacontaplus]", "preferencias", "[Id]=1")
'3   kk = kk + Me![dada] + ".txt"

'comprovo si existeix actualment, i si està surto del programa


'1      hay = ExisteArchivo(kk)
'2      If hay = -1 Then MsgBox "aquest arxiu ja existeix": Exit Sub

' paro els punt 1,2,3,4,5 per que no funciona be


' fabrico l'arxiu
Dim dada As Variant, fatu As Variant, jj As Variant
dada = dada + jj 'calu de l'assantament 1
dada = dada + Format(Me![fecha de altaParte], "aaaammdd") ' data assanta         8
dada = dada + jj 'num assentament       6
dada = dada + jj 'linia                 4
dada = dada + Me![fedeclub].Column(5) 'codi compte           9
dada = dada + jj 'descripcio del compte 30
dada = dada + jj 'concepte assantament  25
dada = dada + jj 'num de document       8
dada = dada + jj 'codide grup           4
dada = dada + jj 'import                13
dada = dada + "D" 'signe   d-h               1
dada = dada + jj 'doci del concepte     2
dada = dada + "  "          'intern                2
dada = dada + "          "  'intern                10
dada = dada + " "           'intern                1


fatu = fatu + jj 'clau de la factura    1
fatu = fatu + Format(Me![fecha de altaParte], "aaaammdd") ' data factura         8
fatu = fatu + jj 'num factura           6
fatu = fatu + jj 'serie factura         3
fatu = fatu + Me![fedeclub].Column(5) 'codi compte           9
fatu = fatu + jj 'codi grup             4
fatu = fatu + jj 'codi concepte         2
fatu = fatu + "D" 'signe  d-h            1
fatu = fatu + jj 'import                13
fatu = fatu + jj 'base                  13
fatu = fatu + jj '%iva                  4
fatu = fatu + jj '%irpf                 5
 





Open kk For Output Shared As #260
Print #260, Me![data]
Print #260, Me![t1]; "#"; Me![t2]; "#"; Me![t3]
Print #260, Me![t2]
Print #260, Me![t3]
Close #260

'ara verifico que s'ha fet l'arxiu
hay = ExisteArchivo(kk)
If hay = 0 Then
    MsgBox "no s'ha fet l'arxiu"
Else
    MsgBox "s'ha fet l'ariux"
End If


End Sub

Private Sub Comando40_Click()
On Error GoTo Err_Comando40_Click

    Dim stDocName As String
    Dim stLinkCriteria As String

    stDocName = "Control Partes"
    DoCmd.OpenForm stDocName, , , stLinkCriteria

Exit_Comando40_Click:
    Exit Sub

Err_Comando40_Click:
    MsgBox Err.Description
    Resume Exit_Comando40_Click
End Sub

Private Sub directivos_AfterUpdate()
ParteDirectivos = Me![directivos]
End Sub

Private Sub ELPrDa_Click()
Dim Respuesta As String
' controla si està activada a protecció i l'elimina
If Me.Form.AllowEdits = False Then
    Respuesta = MsgBox("¿Desea poder modificar datos ya introducidos?", 52)
    
    If Respuesta = vbYes Then
        Me.AllowEdits = True
        Me.AllowAdditions = True: Me.Form.AllowDeletions = True
        Me![parte detallado].Form.AllowEdits = True
        Me![parte detallado].Form.AllowAdditions = True
        Me![parte detallado].Form.AllowDeletions = True
    End If
    
Else
    Respuesta = MsgBox("¿Desea poder proteger datos ya introducidos?", 52)
    
    If Respuesta = vbYes Then
        Me.AllowEdits = False
        Me.AllowAdditions = False
        Me![parte detallado].Form.AllowEdits = False
        Me![parte detallado].Form.AllowAdditions = False
    End If
    
End If
End Sub

Private Sub extranjeros_AfterUpdate()
ParteExtranjeros = Me![extranjeros]
End Sub
Private Sub fecha_AfterUpdate()
Me![año] = Year(Me![fecha]): Me![año].Requery

' per intentar treure l'error
If Not (IsNull(Me![fecha])) Then PaFe = Me![fecha]
End Sub

Private Sub fecha_KeyPress(KeyAscii As Integer)
If KeyAscii = 95 Then Me![fecha] = Date
End Sub

Private Sub fedeclub_AfterUpdate()
' per intentar treure l'error
If Not (IsNull(Me![fedeclub])) Then Pafedecub = Me![fedeclub]
End Sub

Private Sub Form_Activate()

'verificació que la motxilla sigui del programa
If ExisteArchivo("c:\windows\apsdf.txt") = -1 Then Exit Sub
On Error GoTo adeu
ReadBlock
'  Alex
'If Not ((Mid(CodiHasp, 7, 1))) Like "3" Then MsgBox "Esta mochila no corresponde al programa": DoCmd.Quit
Dim p1 As Long, i As Single
Service = HASP_CODE
For i = 1 To 100
Call hasp(CLng(Service), CLng(200), CLng(LptNum), CLng(13423), CLng(15292), Par1&, Par2&, Par3&, Par4&)
p1 = Par1&
        If p1 < 0 Then p1 = p1 + 65536
If IsHASP() = NOT_OK Then
'  Alex
'    i = i + 1: If i > 90 Then MsgBox "no detecto la mochila", vbInformation: DoCmd.Quit
Else
    Exit Sub
End If
Next i
Exit Sub
adeu:
'  Alex
'MsgBox "Esta mochila no corresponde al programa": DoCmd.Quit
End Sub

Private Sub Form_Close()
'Dim   juju As Recordset
'
'Set juju = CurrentDb.QueryDefs("Contalic")

'MsgBox juju.RecordCount
'Dim llices As String
'MsgBox idParte
'llices = DCount("[dniPDet]", "[parte any detallado]", "[id de parte] ='" & juju & "'") '
'MsgBox " els federats al parte son " & Me![Texto12]
'juju.Close
End Sub

Private Sub Form_Current()
If Not (IsNull(Me![partefacturado])) Then
    Me!Comando25.Visible = False
Else
    Me!Comando25.Visible = True
End If
    
If Not (IsNull(Me![facturaplus])) Then
    Me!Comando39.Visible = False
Else
    Me!Comando39.Visible = True
End If


    
ParteDirectivos = Me![directivos]
ParteTecnicos = Me![técnicos]
ParteAficionados = Me![aficionados]

ParteSeniors = Me![seniors]
ParteJuveniles = Me![juveniles]
ParteInfantiles = Me![infantiles]
ParteExtranjeros = Me![extranjeros]


' per intentar treure l'error
If Not (IsNull(Me![fecha])) Then PaFe = Me![fecha]
If Not (IsNull(Me![fedeclub])) Then Pafedecub = Me![fedeclub]


If pafed = 2 Then

    Me![directivos].Visible = False: Me![técnicos].Visible = False
    Me![seniors].Visible = False: Me![juveniles].Visible = False: Me![infantiles].Visible = False
    Me![extranjeros].Visible = False
    'Me![aficionados].Visible = False
    'PaFed.Caption = "parte no federatiu": PaFed.ForeColor = 255
    
    Me![parte detallado].Form![APDet].Visible = False
    Me![parte detallado].Form![EscafandrismoPDet].Visible = False
    Me![parte detallado].Form![FSAPDet].Visible = False
    Me![parte detallado].Form![Foto-cinePDet].Visible = False
    Me![parte detallado].Form![hockeyPDet].Visible = False
    Me![parte detallado].Form![NataciónPDet].Visible = False
    Me![parte detallado].Form![OrientaciónPDet].Visible = False
    Me![parte detallado].Form![PescaPDet].Visible = False
    Me![parte detallado].Form![VideoSubPDeT].Visible = False
    Me![parte detallado].Form![NoCMASPDet].Visible = True




Else
    Me![directivos].Visible = True: Me![técnicos].Visible = True
    Me![seniors].Visible = True: Me![juveniles].Visible = True: Me![infantiles].Visible = True
    Me![extranjeros].Visible = True
    'Me![aficionados].Visible = True
    'PaFed.Caption = "parte federatiu": PaFed.ForeColor = 0
    
    Me![parte detallado].Form![APDet].Visible = True
    Me![parte detallado].Form![EscafandrismoPDet].Visible = True
    Me![parte detallado].Form![FSAPDet].Visible = True
    Me![parte detallado].Form![Foto-cinePDet].Visible = True
    Me![parte detallado].Form![hockeyPDet].Visible = True
    Me![parte detallado].Form![NataciónPDet].Visible = True
    Me![parte detallado].Form![OrientaciónPDet].Visible = True
    Me![parte detallado].Form![PescaPDet].Visible = True
    Me![parte detallado].Form![VideoSubPDeT].Visible = True
    Me![parte detallado].Form![NoCMASPDet].Visible = False


End If

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
    
    Me.AllowEdits = True
    Me.AllowAdditions = True
    Me![parte detallado].Form.AllowEdits = True
    Me![parte detallado].Form.AllowAdditions = True
    
    Me![fecha].SetFocus
Exit_Comando87_Click:
    Exit Sub
Err_Comando87_Click:
    MsgBox Err.Description
    Resume Exit_Comando87_Click
End Sub


Private Sub Comando14_Click()
On Error GoTo Err_Comando14_Click
    DoCmd.Close
Exit_Comando14_Click:
    Exit Sub
Err_Comando14_Click:
    MsgBox Err.Description
    Resume Exit_Comando14_Click
End Sub

Private Sub Form_Open(Cancel As Integer)
If Date > #10/10/2013# Then MsgBox " el programa ha caducat": DoCmd.Quit
End Sub

Private Sub IMPR_AfterUpdate()
Dim Filtre As String
If IMPR Like "licencias ID" Or IMPR Like "licencias id duplicados" Then
Filtre = "([club]  like  '" & Me![fedeclubParte] & "')"
Filtre = Filtre + " and ([nº de relación]  =  " & Me![nº de relación] & ")"
Me.Form.AllowEdits = False
'nº de relación
DoCmd.OpenReport IMPR, acPreview, , Filtre
Exit Sub
Else

Filtre = "([club]  like  '" & Me![fedeclubParte] & "')"
Filtre = Filtre + " and "
Filtre = Filtre + " ([fecha] = #" & Format(Me![fecha de altaParte], "mm/dd/yyyy") & "# )"
Me.Form.AllowEdits = False
DoCmd.OpenReport IMPR, acPreview, , Filtre
End If

End Sub

Private Sub IMPR_GotFocus()
Me.Form.AllowEdits = True
End Sub

Private Sub infantiles_AfterUpdate()
ParteInfantiles = Me![infantiles]
End Sub

Private Sub infantiles_LostFocus()
Me![parte detallado].Form![DNI].SetFocus
End Sub

Private Sub juveniles_AfterUpdate()
ParteJuveniles = Me![juveniles]
End Sub
Private Sub nº_de_relación_GotFocus()
If IsNull([nº de relación]) Then [nº de relación] = (DMax("[nº de relación]", "parte", "añoParte =" & Me![añoParte])) + 1
End Sub

Private Sub pafed_BeforeUpdate(Cancel As Integer)
Me![parte detallado].Form![NoCMASPDet].Visible = False
Me![parte detallado].Form![NoCMASPDet].Visible = False
Me![parte detallado].Form![365].Visible = False
Me![parte detallado].Form![F].Visible = False

If Me![pafed] = 2 Then Me![parte detallado].Form![NoCMASPDet].Visible = True
If Me![pafed] = 6 Then Me![parte detallado].Form![NoCMASPDet].Visible = True
If Me![pafed] = 7 Then Me![parte detallado].Form![365].Visible = True
If Me![pafed] = 8 Then Me![parte detallado].Form![F].Visible = True
If Me![pafed] = 9 Then Me![parte detallado].Form![365].Visible = True




End Sub

Private Sub PaFed_Click()
If pafed = 2 Then
    Me![directivos].Visible = False: Me![técnicos].Visible = False
    Me![seniors].Visible = False: Me![juveniles].Visible = False: Me![infantiles].Visible = False
    Me![extranjeros].Visible = False
    'Me![aficionados].Visible = False
    'PaFed.Caption = "parte no federatiu": PaFed.ForeColor = 255
    
    Me![parte detallado].Form![APDet].Visible = False
    Me![parte detallado].Form![EscafandrismoPDet].Visible = False
    Me![parte detallado].Form![FSAPDet].Visible = False
    Me![parte detallado].Form![Foto-cinePDet].Visible = False
    Me![parte detallado].Form![hockeyPDet].Visible = False
    Me![parte detallado].Form![NataciónPDet].Visible = False
    Me![parte detallado].Form![OrientaciónPDet].Visible = False
    Me![parte detallado].Form![PescaPDet].Visible = False
    Me![parte detallado].Form![VideoSubPDeT].Visible = False
    Me![parte detallado].Form![NoCMASPDet].Visible = True


Else
    Me![directivos].Visible = True: Me![técnicos].Visible = True
    Me![seniors].Visible = True: Me![juveniles].Visible = True: Me![infantiles].Visible = True
    Me![extranjeros].Visible = True
    'Me![aficionados].Visible = True
    'PaFed.Caption = "parte federatiu": PaFed.ForeColor = 0
    
    
    Me![parte detallado].Form![APDet].Visible = True
    Me![parte detallado].Form![EscafandrismoPDet].Visible = True
    Me![parte detallado].Form![FSAPDet].Visible = True
    Me![parte detallado].Form![Foto-cinePDet].Visible = True
    Me![parte detallado].Form![hockeyPDet].Visible = True
    Me![parte detallado].Form![NataciónPDet].Visible = True
    Me![parte detallado].Form![OrientaciónPDet].Visible = True
    Me![parte detallado].Form![PescaPDet].Visible = True
    Me![parte detallado].Form![VideoSubPDeT].Visible = True
    Me![parte detallado].Form![NoCMASPDet].Visible = False


End If
End Sub

Private Sub pafed_LostFocus()
Me![parte detallado].Form![DNI].SetFocus
End Sub

Private Sub seniors_AfterUpdate()
ParteSeniors = Me![seniors]
End Sub
Private Sub técnicos_AfterUpdate()
ParteTecnicos = Me![técnicos]
End Sub
Private Sub Comando24_Click()
On Error GoTo Err_Comando24_Click


    DoCmd.DoMenuItem acFormBar, acEditMenu, 8, , acMenuVer70
    DoCmd.DoMenuItem acFormBar, acEditMenu, 6, , acMenuVer70

Exit_Comando24_Click:
    Exit Sub

Err_Comando24_Click:
    MsgBox "El formulario se encuentra protegido, pulse el botón correspondiente"
    Resume Exit_Comando24_Click
    
End Sub

Private Sub Verificación31_AfterUpdate()
If pafed = 2 Then
    Me![directivos].Visible = False: Me![técnicos].Visible = False
    Me![seniors].Visible = False: Me![juveniles].Visible = False: Me![infantiles].Visible = False
    Me![extranjeros].Visible = False
    'Me![aficionados].Visible = False
    'PaFed.Caption = "parte no federatiu": PaFed.ForeColor = 255
Else
    Me![directivos].Visible = True: Me![técnicos].Visible = True
    Me![seniors].Visible = True: Me![juveniles].Visible = True: Me![infantiles].Visible = True
    Me![extranjeros].Visible = True
    'Me![aficionados].Visible = True
    'PaFed.Caption = "parte federatiu": PaFed.ForeColor = 0
End If

End Sub
