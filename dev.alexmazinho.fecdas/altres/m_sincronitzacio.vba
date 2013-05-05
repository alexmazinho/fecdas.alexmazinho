Option Compare Database

Const ok_color As Long = 52582
Const error_color As Long = 2392063
Const nosync_color As Long = vbRed

' Query per buscar altes, baixes i modificacions a Web que no estan a ACCESS
Const querypartes As String = "SELECT m_partes.*, parte.idParte " _
           & " FROM m_partes LEFT OUTER JOIN parte " _
           & " ON m_partes.idparte_access = parte.idParte " _
           & " WHERE (parte.idParte IS NULL AND m_partes.databaixa IS NULL) OR " _
           & " (parte.idParte IS NOT NULL AND m_partes.databaixa IS NOT NULL) OR" _
           & " (parte.idParte IS NOT NULL AND m_partes.databaixa IS NULL AND" _
           & " (m_partes.tipus <> parte.pafed " _
           & " OR m_partes.club <> parte.fedeclubParte " _
           & " OR m_partes.numrelacio <> parte.[nº de relación] " _
           & " OR Format (m_partes.dataalta , 'yyyy-mm-dd') <> Format (parte.[fecha de altaParte], 'yyyy-mm-dd') " _
           & " OR Format (m_partes.dataalta , 'yyyy') <> parte.[añoParte] " _
           & " OR Format (m_partes.dataentrada , 'yyyy-mm-dd') <> Format (parte.[Fecha de entrada], 'yyyy-mm-dd') " _
           & " OR Format (m_partes.datafacturacio , 'yyyy-mm-dd') <> Format (parte.[partefacturado], 'yyyy-mm-dd') )) "

' ON m_partes.idparte_access = parte.idParte
' Si parte.idParte IS NULL --> No existeix parte taula ACCESS
    ' Si m_partes.databaixa IS NULL  --> Cal donar-la d'alta
    ' Else --> No cal fer res
' Else  --> Si existeix parte taula ACCESS
    ' Si m_partes.databaixa IS NULL  --> Mirar si hi ha canvis i modificar
    ' Else --> Donar de baixa

' Query per buscar altes, baixes i modificacions a Web que no estan a ACCESS
Const querydadespersonals As String = "SELECT m_persones.*, [datos personales].DNI " _
          & " FROM m_persones LEFT OUTER JOIN [datos personales] " _
          & " ON m_persones.dni = [datos personales].DNI " _
          & " WHERE ([datos personales].DNI IS NULL AND m_persones.databaixa IS NULL) OR " _
          & " ([datos personales].DNI IS NOT NULL AND m_persones.databaixa IS NOT NULL) OR" _
          & " ([datos personales].DNI IS NOT NULL AND m_persones.databaixa IS NULL AND" _
          & " (m_persones.nom <> [datos personales].nom " _
          & " OR m_persones.cognoms <> [datos personales].cognoms " _
          & " OR m_persones.dni <> [datos personales].DNI " _
          & " OR Format (m_persones.datanaixement , 'yyyy-mm-dd') <> Format ([datos personales].dn, 'yyyy-mm-dd') " _
          & " OR m_persones.sexe <> [datos personales].SEXO " _
          & " OR nz(m_persones.telefon1, 0) <> nz([datos personales].telf, 0) " _
          & " OR nz(m_persones.telefon2, 0) <> nz([datos personales].telef2dp,0) " _
          & " OR nz(m_persones.mail, '') <> nz([datos personales].mail, '') " _
          & " OR m_persones.addradreca <> [datos personales].dir " _
          & " OR nz(m_persones.addrpob, '') <> nz([datos personales].pob, '') " _
          & " OR m_persones.addrcp <> [datos personales].cpDp " _
          & " OR ucase(m_persones.addrprovincia) <> ucase([datos personales].provDP) " _
          & " OR nz(ucase(m_persones.addrcomarca), '') <> nz(ucase([datos personales].Comarca_Dp), '') " _
          & " OR m_persones.addrnacionalitat <> [datos personales].nacionalidad )) "
          '& " OR nz(m_persones.club, '') <> nz([datos personales].fedeclubDP, '') )) "
        ' NO comprovo el club, pq al web pot estar duplicada la persona

' ON m_persones.dni = [datos personales].DNI
' Si [datos personales].DNI IS NULL --> No existeix persona amb aquest dni taula ACCESS
    ' Si m_persones.databaixa IS NULL  --> Cal donar-la d'alta
    ' Else --> No cal fer res
' Else  --> Si existeix persona amb aquest dni taula ACCESS
    ' Si m_persones.databaixa IS NULL  --> Mirar si hi ha canvis i modificar
    ' Else --> Donar de baixa

Const queryclubs As String = "SELECT DISTINCT clubs.fedeclub, clubs.TipoClub, clubs.[N CLUB C], " _
          & "clubs.[TELÉFONO CLUB], clubs.[email], clubs.[WebClub], clubs.[NIF CLUB], " _
          & "clubs.[DIRECCIÓN CLUB], clubs.[POBLACIÓN CLUB], clubs.[CP CLUB], clubs.[PROVINCIA CLUB] " _
          & "FROM clubs LEFT OUTER JOIN m_clubs " _
          & "ON m_clubs.codi = clubs.fedeclub " _
          & "AND nz(m_clubs.tipus,0) = nz(clubs.TipoClub,0) " _
          & "AND m_clubs.nom = clubs.[N CLUB C] " _
          & "AND nz(m_clubs.telefon,0) = nz(clubs.[TELÉFONO CLUB],0) " _
          & "AND nz(m_clubs.mail,'') = nz(clubs.[email],'') " _
          & "AND nz(m_clubs.web,'') = nz(clubs.[WebClub],'') " _
          & "AND m_clubs.cif = clubs.[NIF CLUB] " _
          & "AND nz(m_clubs.addradreca,'') = nz(clubs.[DIRECCIÓN CLUB],'') " _
          & "AND nz(m_clubs.addrpob,'') = nz(clubs.[POBLACIÓN CLUB],'') " _
          & "AND nz(m_clubs.addrcp,'') = nz(clubs.[CP CLUB],'') " _
          & "AND nz(m_clubs.addrprovincia,'') = nz(clubs.[PROVINCIA CLUB],'') " _
          & "WHERE m_clubs.codi IS NULL"

Const querytipusclub As String = "SELECT DISTINCT TipoClub.idTipoClub, " _
            & "TipoClub.[Tipo Club] FROM TipoClub LEFT JOIN m_tipusclub " _
            & "ON TipoClub.idTipoClub = m_tipusclub.id " _
            & "WHERE m_tipusclub.id Is Null"

Const querytipusparte As String = "SELECT DISTINCT TipoParte.idTipoparte, " _
             & "TipoParte.TipoparteCod, TipoParte.Tipoparte " _
             & "FROM TipoParte LEFT JOIN m_tipusparte ON " _
             & "(TipoParte.Tipoparte = m_tipusparte.descripcio) AND " _
             & "(TipoParte.TipoparteCod = m_tipusparte.codi) AND " _
             & "(TipoParte.idTipoparte = m_tipusparte.id) " _
             & "WHERE m_tipusparte.id IS NULL "

Const querycategories As String = "SELECT DISTINCT m_categorias_access.códigosalidacategoria, m_categorias_access.idTipoparte , " _
          & "m_categorias_access.IdCategoria, m_categorias_access.[categoria definición] " _
          & "FROM m_categorias_access LEFT OUTER JOIN m_categories " _
          & "ON m_categories.tipusparte = m_categorias_access.idTipoparte " _
          & "AND m_categories.simbol = m_categorias_access.IdCategoria " _
          & "AND m_categories.categoria = m_categorias_access.[categoria definición] " _
          & "AND m_categories.codisortida = m_categorias_access.códigosalidacategoria " _
          & " WHERE m_categories.codisortida IS NULL"
          

Private Sub Form_Load()

    tabdadespersonals
    
    tabpartes
    
    Me![smsclubs].Caption = "Pendent de sincronitzar"
    Me![smsclubs].ForeColor = error_color
    
    Me![resumclubs].Caption = "Pendent de sincronitzar la taula de Clubs"
    Me![resumclubs].ForeColor = error_color
    
    checktipusclub_Click
    
    checktipusparte_Click
    
    checkcategories_Click
   
End Sub

Private Sub fullessincronitzacio_Change()
    Select Case Me!fullessincronitzacio.Value ' Returns Page Index.
      Case 0  ' Page Index for Page 1.
         Form_Load
      Case 2  ' Page Index for Page 2.
         tabpartes
      Case 3  ' Page Index for Page 2.
         tabdadespersonals
   End Select
End Sub

Private Sub tabpartes()
    Dim partes_lu As String
    Dim actualitzacio As Variant
    
    partes_lu = updatedataactualitzacio("partes")
    Me![smslastupdatepartes].Caption = partes_lu
    
    If (Me![smspartes].ForeColor = error_color) Then
        Me![smspartes].Caption = "Pendent de sincronitzar"
        Me![smspartes].ForeColor = error_color
    
        Me![resumpartes].Caption = "Pendent de sincronitzar la taula de Partes " & "(" & partes_lu & ")"
        Me![resumpartes].ForeColor = error_color
    
    End If
End Sub

Private Sub tabdadespersonals()
    Dim dadespersonals_lu As String
    Dim actualitzacio As Variant
    
    dadespersonals_lu = updatedataactualitzacio("dadespersonals")
    Me![smslastupdatedadespersonals].Caption = dadespersonals_lu

    If (Me![smsdadespersonalsweb].ForeColor = error_color) Then
        Me![smsdadespersonalsweb].Caption = "Pendent de sincronitzar"
        Me![smsdadespersonalsweb].ForeColor = error_color
    
        Me![resumdadespersonals].Caption = "Pendent de sincronitzar la taula de Dades Personals " & "(" & dadespersonals_lu & ")"
        Me![resumdadespersonals].ForeColor = error_color
    End If
End Sub

Private Function updatedataactualitzacio(taula As String) As String
    Dim actualitzacio As Variant
    
    actualitzacio = dataactualitzacio(taula)
    
    If actualitzacio = "" Then
        updatedataactualitzacio = "Encara no s'ha sincronitzat cap vegada"
    Else
        updatedataactualitzacio = "Darrera sincronització: " & actualitzacio
    End If
End Function


Private Sub checkpartes_Click()
    Dim actualitzacio As String
    
    'actualitzacio = msglastupdate("partes")
    'If actualitzacio = "Exit" Then Exit Sub
    actualitzacio = dataactualitzacio("partes")
    
    partes_action True, actualitzacio, Me![smspartes], Me![resumpartes], Me![taulapartes]
End Sub

Private Sub checkdadespersonals_Click()
    Dim actualitzacio As String
    
    actualitzacio = msglastupdate("dadespersonals")
    
    If actualitzacio = "Exit" Then Exit Sub
    
    dadespersonals_action True, actualitzacio, Me![smsdadespersonalsweb], Me![resumdadespersonals], Me![tauladadespersonalsweb]

End Sub


Private Sub checkclubs_Click()
    Dim camps(0 To 10) As String
    
    camps(0) = "club"
    camps(1) = "tipus"
    camps(2) = "nom"
    camps(3) = "telefon"
    camps(4) = "mail"
    camps(5) = "web"
    camps(6) = "cif"
    camps(7) = "adreca"
    camps(8) = "poblacio"
    camps(9) = "cp"
    camps(10) = "provincia"
    
    checktaula queryclubs, "Clubs", Me![smsclubs], Me![resumclubs], camps, Me![taulaclubs]
End Sub

Private Sub checktipusclub_Click()
    Dim camps(0 To 1) As String
    
    camps(0) = "id"
    camps(1) = "tipus"

    checktaula querytipusclub, "Tipus de club", Me![smstipusclub], Me![resumtipusclubs], camps, Me![taulatipusclub]
End Sub

Private Sub checktipusparte_Click()
    Dim camps(0 To 2) As String

    camps(0) = "id"
    camps(1) = "tipus"
    camps(2) = "descripcio"

    checktaula querytipusparte, "Tipus de parte", Me![smstipusparte], Me![resumtipusparte], camps, Me![taulatipusparte]

End Sub

Private Sub checkcategories_Click()
    Dim camps(0 To 3) As String
    
    'Ull cal que estigui creada la vista  "m_categorias_access"
    ' SELECT categorias.códigosalidacategoria, TipoParte.idTipoparte, categorias.IdCategoria, categorias.[categoria definición]
    ' FROM categorias INNER JOIN TipoParte ON categorias.tipoparte = TipoParte.idTipoparte

    camps(0) = "codi sortida"
    camps(1) = "tipus parte"
    camps(2) = "lletra categoria"
    camps(3) = "categoria"

    checktaula querycategories, "Categories", Me![smscategories], Me![resumcategories], camps, Me![taulacategories]
End Sub


Private Sub syncpartes_Click()
    Dim actualitzacio As String
    
    'actualitzacio = msglastupdate("partes")
    'If actualitzacio = "Exit" Then Exit Sub
    
    actualitzacio = dataactualitzacio("partes")
    partes_action False, actualitzacio, Me![smspartes], Me![resumpartes], Me![taulapartes]
    
    actualitzacio = dataactualitzacio("partes")
    partes_action True, actualitzacio, Me![smspartes], Me![resumpartes], Me![taulapartes]
End Sub


Private Sub partes_action(check As Boolean, actualitzacio As String, text As Label, Resum As Label, llista As ListBox)
On Error Resume Next
    
    Dim rst As Recordset
    Dim query As String, error As String, partes_lu As String
    Dim j As Integer
    Dim idParte As Variant
    Dim updatedate As Boolean
    Dim syncok As Boolean
    Dim ws As DAO.Workspace
    Dim db As DAO.Database
    
    syncok = True
    
    text.BorderStyle = 0
    llista.Visible = False
    
    ' Muntar taula no sincronitzats
    llista.RowSource = ""
    llista.ColumnCount = 5
    llista.ColumnHeads = True
    llista.AddItem "tipus;club;relacio;id;accio"
    
    Set ws = DBEngine.Workspaces(0)
    Set db = CurrentDb
    
    'If actualitzacio = "" Then
    ' Totes les dades
        'query = querypartes   'Prova només mirar modificades ********************
        query = "SELECT m_partes.*, NULL FROM m_partes "
        query = query & " WHERE m_partes.datamodificacio > #" & Format(actualitzacio, "yyyy-mm-dd hh:mm:ss") & "#"
    'Else
    ' Query simplificada per buscar altes, baixes i modificacions a Web que no estan a ACCESS des de última actualització
    ' Des de darrera actualització
    '    query = "SELECT m_partes.*, NULL FROM m_partes "
    '    query = query & " WHERE m_partes.datamodificacio > #" & Format(actualitzacio, "yyyy-mm-dd hh:mm:ss") & "#"
    'End If
    
    Set rst = db.OpenRecordset(query, dbOpenDynaset)
    
    If Err.Number > 0 Then
        error = "Error en la connexió a la base de dades "
        GoTo Errordb
    End If
   
    updatedate = True
    
    If Not (rst.EOF) Then
        rst.MoveFirst
        
        Do While Not rst.EOF
            'If (actualitzacio = "") Then       ' Tot, 'Prova només mirar modificades ********************
                'idParte = rst.Fields("idParte")
            'Else       ' Des de últim update
                idParte = rst.Fields("idparte_access")
            'End If
                
            If IsNull(idParte) Then  ' No existeix a ACCESS
                If IsNull(rst.Fields("databaixa")) Then   'Sense Data baixa
                    ' Donar alta
                    syncok = False
                    
                    If Not check Then
                        updatedate = altaparte_db(rst, ws, db)
                    Else
                        llista.AddItem rst.Fields("tipus") & ";" & rst.Fields("club") _
                                    & ";" & rst.Fields("numrelacio") & ";" & rst.Fields("idparte_access") & "; Alta "
                    End If
                Else
                    ' Nothing to do
                End If
            Else    ' Existeix a ACCESS
                syncok = False
                If IsNull(rst.Fields("databaixa")) Then   'Sense Data baixa
                    ' Modificar dades existents a ACCESS amb les dades del Web
                    If Not check Then
                        updatedate = updateparte_db(rst, idParte, ws, db, actualitzacio)
                    Else
                        llista.AddItem rst.Fields("tipus") & ";" & rst.Fields("club") _
                                   & ";" & rst.Fields("numrelacio") & ";" & rst.Fields("idparte_access") & "; Modificació "
                    End If
                Else
                    ' Donar de baixa
                    If Not check Then
                        updatedate = baixaparte_db(rst, idParte, ws, db)
                    Else
                        llista.AddItem rst.Fields("tipus") & ";" & rst.Fields("club") _
                                   & ";" & rst.Fields("numrelacio") & ";" & rst.Fields("idparte_access") & "; Baixa "
                    End If
                End If
            End If
            rst.MoveNext
        Loop
    End If
    
    If Not rst Is Nothing Then
        rst.Close
        Set rst = Nothing
    End If
    
    If Err.Number > 0 Then
        error = "Error"
        query = ""
        GoTo Errordb
    End If

    If check Then  ' Només comprovant
        If syncok Then
            text.Caption = "Taula Partes Web -> Local Sincronitzada correctament"
            text.ForeColor = ok_color
            Resum.Caption = "Taula Partes Web -> Local Sincronitzada correctament"
            Resum.ForeColor = ok_color
        Else
            text.Caption = "Les següents dades a la taula Partes Web -> Local" _
                & ", no estan sincronitzades amb l'aplicació web"
            text.ForeColor = nosync_color
            Resum.Caption = "Cal sincronitzar la taula Partes Web -> Local"
            Resum.ForeColor = nosync_color
            llista.Visible = True
        End If
    Else    ' Fent sync
        ' si tot ok, modificar data última actualització
        If updatedate Then
            query = "UPDATE m_lastupdate SET lastupdate = now WHERE taula = 'partes'"
            db.Execute query
            If Err.Number > 0 Then
                error = "Error actualitzant data de sincronització"
                GoTo Errordb
            End If
            'partes_lu = updatedataactualitzacio("partes")
            'Me![smslastupdatepartes].Caption = partes_lu
        End If
    End If
    
Exit_partes_action:
    Exit Sub

Errordb:
    If Not check Then
        MsgBox error & vbCrLf & Err.Description
    Else
        text.Caption = Err.Description & vbCrLf & error
        text.ForeColor = error_color
        Resum.Caption = Err.Description & vbCrLf & error
        Resum.ForeColor = error_color
        llista.Visible = False
    End If
    
    logerror_db Err.Number, Err.Description, error & vbCrLf & Err.Description, "m_sincronitzacio", "partes_action", query
End Sub

Private Function altaparte_db(rst As Recordset, ws As DAO.Workspace, db As DAO.Database) As Boolean
On Error GoTo Errordb
    Dim idParte As Variant, numRelacio As Variant
    Dim queryabm As String, query As String, strIdParte As String
    Dim errorstr As String
    Dim rst_detall As Recordset
    Dim DNI As Variant, categoria As Variant
    Dim rs As DAO.Recordset
    Dim idpartedetall As Long
                
    If isdatapartesok(rst) Then
        ws.BeginTrans
                            
        queryabm = queryinsertpartes(rst)
        db.Execute queryabm, dbFailOnError
                            
        ' Recuperar id de replica i num relacio
        queryabm = queryselectpartes(rst)
        idParte = db.OpenRecordset(queryabm)("idParte")
        If IsNull(idParte) Then GoTo Errordb
        numRelacio = db.OpenRecordset(queryabm)("[nº de relación]")
        If IsNull(numRelacio) Then GoTo Errordb
        
        'Actualitzar registre web amb nou id i num relacio
        strIdParte = Mid(idParte, 7, 38)
        queryabm = queryupdatem_partes(rst, strIdParte, numRelacio)
        db.Execute queryabm, dbFailOnError
                                                       
        'Recuperar llicències del parte per inserir (inclus esborrades)
        'query = "SELECT * FROM m_llicencies WHERE parte = " & rst.Fields("id") & " AND databaixa IS NULL"
        query = "SELECT * FROM m_llicencies WHERE parte = " & rst.Fields("id")
                
        Set rst_detall = db.OpenRecordset(query, dbOpenDynaset)
        If Not (rst_detall.EOF) Then
            rst_detall.MoveFirst
            Do While Not rst_detall.EOF
                
                queryabm = "codisortida = " & rst_detall.Fields("categoria") & ""
                categoria = DLookup("simbol", "m_categories", queryabm)
                If IsNull(categoria) Then GoTo Errordb
                
                queryabm = "id = " & rst_detall.Fields("persona") & ""
                DNI = DLookup("dni", "m_persones", queryabm)
                If IsNull(DNI) Then GoTo Errordb
                
                ' Consultar si exiteix DNI a ACCESS, en cas contrari avís a l'usuari per sincronitzar dades personals primer
                queryabm = "dni = '" & DNI & "'"
                DNI = DLookup("DNI", "datos personales", queryabm)
                If IsNull(DNI) Then
                    ws.Rollback
                    rst_detall.Close
                    MsgBox "Cal sincronitzar les dades personals primer"
                    Exit Function
                End If
                                
                ' Inserir llicència
                queryabm = queryinsertpartedetall(rst, rst_detall, idParte, DNI, categoria)
                db.Execute queryabm, dbFailOnError
                
                ' Recuperar id del parte detall inserit
                Set rs = db.OpenRecordset("SELECT @@IDENTITY AS LastID;")
                idpartedetall = rs!LastID
                
                queryabm = "UPDATE m_llicencies SET idpartedetall_access = " & idpartedetall & "," _
                         & " idparte_access = '" & strIdParte & "'" _
                         & " WHERE id = " & rst_detall.Fields("id")
                
                db.Execute queryabm, dbFailOnError
                
                rst_detall.MoveNext
            Loop
        End If
        
        ws.CommitTrans
    Else
        errorstr = "Partes (" & rst.Fields("tipus") & ", '" & rst.Fields("club") & "', " _
                        & rst.Fields("numrelacio") & ", '" & rst.Fields("idparte_access") & "') amb dades incorrectes (Alta)"
        GoTo Errordades
    End If

    If Not rst_detall Is Nothing Then
        rst_detall.Close
        Set rst_detall = Nothing
    End If

Exit_altaparte_db:
    altaparte_db = True
    Exit Function

Errordb:
    errorstr = "Error insert parte web -> Access"
    ws.Rollback
    
Errordades:
    altaparte_db = False  ' no actualitzar updatedate
    logerror_db Err.Number, Err.Description, errorstr, "m_sincronitzacio", "altaparte_db", queryabm
    
End Function

Private Function baixaparte_db(rst As Recordset, idParte As Variant, ws As DAO.Workspace, db As DAO.Database) As Boolean
On Error GoTo Errordb
    Dim queryabm As String
    Dim errorstr As String
    
    If isdatapartesok(rst) Then
        ws.BeginTrans
                                    
        ' Esborrar llicències del parte
        queryabm = "DELETE FROM [parte detallado] WHERE [parte detallado].[id de parte] = " & idParte
        db.Execute queryabm, dbFailOnError
        
        queryabm = querydeletepartes(idParte)
        db.Execute queryabm, dbFailOnError

        ws.CommitTrans
    Else
        errorstr = "Partes (" & rst.Fields("tipus") & ", '" & rst.Fields("club") & "', " _
                        & rst.Fields("numrelacio") & ", '" & rst.Fields("idparte_access") & "') amb dades incorrectes (baixa)"
        GoTo Errordades
    End If

Exit_baixaparte_db:
    baixaparte_db = True
    Exit Function

Errordb:
    errorstr = "Error baixa parte web -> Access"
    ws.Rollback
    
Errordades:
    logerror_db Err.Number, Err.Description, errorstr, "m_sincronitzacio", "baixaparte_db", queryabm
    baixaparte_db = False  ' no actualitzar updatedate

End Function

Private Function updateparte_db(rst As Recordset, idParte As Variant, ws As DAO.Workspace, db As DAO.Database, actualitzacio As String) As Boolean
On Error GoTo Errordb
    Dim queryabm As String
    Dim errorstr As String
    Dim rst_detall As Recordset
    Dim DNI As Variant, categoria As Variant
    Dim rs As DAO.Recordset
    Dim idpartedetall As Long
       
    If isdatapartesok(rst) Then
        ws.BeginTrans
                            
        queryabm = queryupdatepartes(rst, idParte)
        db.Execute queryabm, dbFailOnError

        'Recuperar llicències del parte per comprovar si cal modificar
        queryabm = "SELECT m_llicencies.*, [parte detallado].* FROM " _
                 & " m_llicencies LEFT JOIN [parte detallado] " _
                 & " ON m_llicencies.idpartedetall_access = [parte detallado].idPartedet " _
                 & " WHERE parte = " & rst.Fields("id")
        
        'If actualitzacio <> "" Then
            ' Des de darrera actualització
            queryabm = queryabm & " AND m_llicencies.datamodificacio > #" & Format(actualitzacio, "yyyy-mm-dd hh:mm:ss") & "#"
        'End If
                
        Set rst_detall = db.OpenRecordset(queryabm, dbOpenDynaset)
        If Not (rst_detall.EOF) Then
            rst_detall.MoveFirst
            Do While Not rst_detall.EOF
                
                queryabm = "codisortida = " & rst_detall.Fields("categoria") & ""
                categoria = DLookup("simbol", "m_categories", queryabm)
                If IsNull(categoria) Then GoTo Errordb
                
                queryabm = "id = " & rst_detall.Fields("persona") & ""
                DNI = DLookup("dni", "m_persones", "id = " & rst_detall.Fields("persona") & "")
                If IsNull(DNI) Then GoTo Errordb
                
                If IsNull(rst_detall.Fields("idPartedet")) Then
                ' NO existeix a ACCESS
                    If IsNull(rst_detall.Fields("databaixa")) Then  ' Alta
                        queryabm = queryinsertpartedetall(rst, rst_detall, idParte, DNI, categoria)
                        db.Execute queryabm, dbFailOnError
                        
                        ' Recuperar id del parte detall inserit
                        Set rs = db.OpenRecordset("SELECT @@IDENTITY AS LastID;")
                        idpartedetall = rs!LastID
                
                        queryabm = "UPDATE m_llicencies SET idpartedetall_access = " & idpartedetall _
                                 & " WHERE id = " & rst_detall.Fields("id")
                        
                        db.Execute queryabm, dbFailOnError
                    Else
                    ' Donada de baixa, no cal fer res
                    End If
                Else
                ' Existeix a ACCESS
                    If IsNull(rst_detall.Fields("databaixa")) Then  ' Modificació
                        ' Existeix, mirar si ha canviat per modificar
                        If isllicenciachanged(rst_detall, DNI, categoria) Then
                            queryabm = queryupdatepartedetall(rst_detall, idParte, DNI, categoria)
                            db.Execute queryabm, dbFailOnError
                        End If
                    Else
                    ' De baixa, cal esborrar ACCESS
                        queryabm = "DELETE FROM [parte detallado] " _
                                 & " WHERE [parte detallado].idPartedet = " _
                                 & rst_detall.Fields("idPartedet")
                        db.Execute queryabm, dbFailOnError
                    End If
                End If
                
                rst_detall.MoveNext
            Loop
        End If

        ws.CommitTrans
    Else
        errorstr = "Partes (" & rst.Fields("tipus") & ", '" & rst.Fields("club") & "', " _
                        & rst.Fields("numrelacio") & ", '" & rst.Fields("idparte_access") & "') amb dades incorrectes (Modificació)"
        GoTo Errordades
    End If

    If Not rst_detall Is Nothing Then
        rst_detall.Close
        Set rst_detall = Nothing
    End If
    
Exit_updateparte_db:
    updateparte_db = True
    Exit Function

Errordb:
    errorstr = "Error modificació parte web -> Access"
    ws.Rollback
    
Errordades:
    logerror_db Err.Number, Err.Description, errorstr, "m_sincronitzacio", "updateparte_db", queryabm
    updateparte_db = False  ' no actualitzar updatedate

End Function


Private Function isdatapartesok(rst As Recordset) As Boolean
    ' Telefons, mail i comarca poden ser null
    If (IsNull(rst.Fields("tipus")) Or rst.Fields("tipus") = 0 Or _
        IsNull(rst.Fields("club")) Or rst.Fields("club") = "" Or _
        IsNull(rst.Fields("dataalta")) Or rst.Fields("dataalta") = "" Or _
        IsNull(rst.Fields("dataentrada")) Or rst.Fields("dataentrada") = "") Then
        isdatapartesok = False
    Else
        isdatapartesok = True
    End If
End Function

Private Function querydeletepartes(idParte As Variant) As String
    Dim query As String

    query = "DELETE FROM parte "
    query = query & " WHERE parte.idParte = '" & idParte & "'"
    
    querydeletepartes = query
End Function
    
Private Function queryselectpartes(rst As Recordset) As String
    Dim query As String

    query = "SELECT idParte, parte.[nº de relación] FROM parte WHERE "
    'Recuperar id web des de camp idvell
    query = query & " parte.idvell = " & rst.Fields("id")
    
    'query = query & " parte.pafed = " & rst.Fields("tipus")
    'query = query & " AND parte.fedeclubParte = '" & rst.Fields("club") & "' "
    'query = query & " AND parte.[nº de relación] = " & rst.Fields("numrelacio")
    'query = query & " AND Format(parte.[fecha de altaParte], 'dd/mm/yyyy') = '" & Format(rst.Fields("dataalta"), "dd/mm/yyyy") & "' "
    
    queryselectpartes = query
End Function
    
Private Function queryupdatem_partes(rst As Recordset, idParte As Variant, numRelacio As Variant) As String
    Dim query As String

    query = "UPDATE m_partes SET idparte_access = '" _
          & idParte & "', numrelacio = " & numRelacio & "  WHERE id = " & rst.Fields("id")
    
    queryupdatem_partes = query
End Function

    
Private Function queryupdatepartes(rst As Recordset, idParte As Variant) As String
    Dim query As String

    query = "UPDATE parte SET "
    
    query = query & " parte.pafed = " & rst.Fields("tipus") & ","
    query = query & " parte.fedeclubParte = '" & rst.Fields("club") & "',"
    query = query & " parte.[nº de relación] = " & rst.Fields("numrelacio") & ","
    query = query & " parte.[fecha de altaParte] = '" & rst.Fields("dataalta") & "',"
    query = query & " parte.[añoParte] = '" & Format(rst.Fields("dataalta"), "yyyy") & "',"
    query = query & " parte.[Fecha de entrada] = '" & rst.Fields("dataentrada") & "'"
    
    ' NO modificar parte facturado
    'query = query & " parte.[Fecha de entrada] = '" & rst.Fields("dataentrada") & "',"
    'If IsNull(rst.Fields("datapagament")) Then
    '    query = query & " parte.[partefacturado] = NULL"
    'Else
    '    query = query & " parte.[partefacturado] = '" & rst.Fields("datapagament") & "'"
    'End If
    
    query = query & " WHERE parte.idParte = '" & idParte & "'"
    
    queryupdatepartes = query
    
End Function

Private Function queryinsertpartes(rst As Recordset) As String
    Dim query As String
    Dim numRelacio As Variant
    
    ' Afegir el id de la web al camp idvell per poder recuperar-lo
    query = "INSERT INTO parte (pafed, fedeclubParte, [nº de relación], " _
                          & "[fecha de altaParte], [añoParte], [Fecha de entrada], " _
                          & "[partefacturado], idvell) VALUES ("
                     
    numRelacio = (DMax("[nº de relación]", "parte", "añoParte =" & Format(rst.Fields("dataalta"), "yyyy"))) + 1
                     
    query = query & rst.Fields("tipus") & ", "
    query = query & "'" & rst.Fields("club") & "', "
    ' Num relació es calcula sol
    query = query & numRelacio & ", "
    query = query & "'" & rst.Fields("dataalta") & "', "
    query = query & Format(rst.Fields("dataalta"), "yyyy") & ", "
    query = query & "'" & rst.Fields("dataentrada") & "', "
    query = query & " NULL, "
    query = query & rst.Fields("id")  ' A idvell per poder recuperar després
    
    ' NO modificar parte facturado
    ' query = query & "'" & rst.Fields("dataentrada") & "', "
    'If IsNull(rst.Fields("datapagament")) Then
    '    query = query & " NULL "
    'Else
    '    query = query & rst.Fields("datapagament") & " "
    'End If
    query = query & ")"

    queryinsertpartes = query
End Function

Private Function queryinsertpartedetall(rst As Recordset, rst_detall As Recordset, idParte As Variant, DNI As Variant, categoria As Variant) As String
    Dim query As String
    
    query = "INSERT INTO [parte detallado] (" _
                         & "dniPDet , fechaParteDet, fedeclubParteDet, FechaEntradaPDet, " _
                         & "[id de parte], categoriaParteDet, DataCaducitatPDET, " _
                         & "PescaPDet,  EscafandrismoPDet, NataciónPDet, OrientaciónPDet, " _
                         & "BiologíaPDet, [Foto-cinePDet], hockeyPDet, FSAPDet, " _
                         & "VideoSubPDeT, APDet, NoCMASPDet, F) VALUES ("
                                
    query = query & "'" & DNI & "',"
    query = query & "'" & rst.Fields("dataentrada") & "',"  ' Entrada del parte
    query = query & "'" & rst.Fields("club") & "',"
    query = query & "'" & rst_detall.Fields("dataentrada") & "',"  ' Entrada de la llicència
    query = query & "'" & Mid(idParte, 7, 38) & "',"
    query = query & "'" & categoria & "',"
    query = query & "'" & rst_detall.Fields("datacaducitat") & "',"
    query = query & IIf(rst_detall.Fields("pesca") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("escafandrisme") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("natacio") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("orientacio") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("biologia") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("fotocine") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("hockey") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("fotosubapnea") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("videosub") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("apnea") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("nocmas") = 1, vbTrue, vbFalse) & ","
    query = query & IIf(rst_detall.Fields("fusell") = 1, vbTrue, vbFalse) & ")"
    
    queryinsertpartedetall = query
End Function


Private Function queryupdatepartedetall(rst_detall As Recordset, idParte As Variant, DNI As Variant, categoria As Variant) As String
    Dim query As String
    
    query = "UPDATE [parte detallado] SET "
    query = query & " dniPDet = '" & DNI & "',"
    query = query & " categoriaParteDet = '" & categoria & "',"
    query = query & " FechaEntradaPDet = '" & rst_detall.Fields("dataentrada") & "',"
    query = query & " DataCaducitatPDET = '" & rst_detall.Fields("datacaducitat") & "',"
    query = query & " PescaPDet = " & IIf(rst_detall.Fields("pesca") = 1, vbTrue, vbFalse) & ","
    query = query & " EscafandrismoPDet = " & IIf(rst_detall.Fields("escafandrisme") = 1, vbTrue, vbFalse) & ","
    query = query & " NataciónPDet = " & IIf(rst_detall.Fields("natacio") = 1, vbTrue, vbFalse) & ","
    query = query & " OrientaciónPDet = " & IIf(rst_detall.Fields("orientacio") = 1, vbTrue, vbFalse) & ","
    query = query & " BiologíaPDet = " & IIf(rst_detall.Fields("biologia") = 1, vbTrue, vbFalse) & ","
    query = query & " [Foto-cinePDet] = " & IIf(rst_detall.Fields("fotocine") = 1, vbTrue, vbFalse) & ","
    query = query & " hockeyPDet = " & IIf(rst_detall.Fields("hockey") = 1, vbTrue, vbFalse) & ","
    query = query & " FSAPDet = " & IIf(rst_detall.Fields("fotosubapnea") = 1, vbTrue, vbFalse) & ","
    query = query & " VideoSubPDeT = " & IIf(rst_detall.Fields("videosub") = 1, vbTrue, vbFalse) & ","
    query = query & " APDet = " & IIf(rst_detall.Fields("apnea") = 1, vbTrue, vbFalse) & ","
    query = query & " NoCMASPDet = " & IIf(rst_detall.Fields("nocmas") = 1, vbTrue, vbFalse) & ","
    query = query & " F = " & IIf(rst_detall.Fields("fusell") = 1, vbTrue, vbFalse)
    query = query & " WHERE idParteDet = " & rst_detall.Fields("idParteDet")

    queryupdatepartedetall = query
End Function

Private Function isllicenciachanged(rst_detall As Recordset, DNI As Variant, categoria As Variant) As Boolean

    If (DNI <> rst_detall.Fields("dniPDet") Or _
        categoria <> rst_detall.Fields("categoriaParteDet") Or _
        Format(rst_detall.Fields("dataentrada"), "yyyy-mm-dd") <> Format(rst_detall.Fields("FechaEntradaPDet"), "yyyy-mm-dd") Or _
        Format(rst_detall.Fields("datacaducitat"), "yyyy-mm-dd") <> Format(rst_detall.Fields("DataCaducitatPDET"), "yyyy-mm-dd")) Then
        isllicenciachanged = True
        Exit Function
    End If
    
    If (rst_detall.Fields("pesca") = 1 And rst_detall.Fields("PescaPDet") = vbFalse) Or _
       (rst_detall.Fields("pesca") = 0 And rst_detall.Fields("PescaPDet") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("escafandrisme") = 1 And rst_detall.Fields("EscafandrismoPDet") = vbFalse) Or _
       (rst_detall.Fields("escafandrisme") = 0 And rst_detall.Fields("EscafandrismoPDet") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("natacio") = 1 And rst_detall.Fields("NataciónPDet") = vbFalse) Or _
       (rst_detall.Fields("natacio") = 0 And rst_detall.Fields("NataciónPDet") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("orientacio") = 1 And rst_detall.Fields("OrientaciónPDet") = vbFalse) Or _
       (rst_detall.Fields("orientacio") = 0 And rst_detall.Fields("OrientaciónPDet") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("biologia") = 1 And rst_detall.Fields("BiologíaPDet") = vbFalse) Or _
       (rst_detall.Fields("biologia") = 0 And rst_detall.Fields("BiologíaPDet") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("fotocine") = 1 And rst_detall.Fields("[Foto-cinePDet]") = vbFalse) Or _
       (rst_detall.Fields("fotocine") = 0 And rst_detall.Fields("[Foto-cinePDet]") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("hockey") = 1 And rst_detall.Fields("hockeyPDet") = vbFalse) Or _
       (rst_detall.Fields("hockey") = 0 And rst_detall.Fields("hockeyPDet") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("fotosubapnea") = 1 And rst_detall.Fields("FSAPDET") = vbFalse) Or _
       (rst_detall.Fields("fotosubapnea") = 0 And rst_detall.Fields("FSAPDET") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("videosub") = 1 And rst_detall.Fields("VideoSubPDeT") = vbFalse) Or _
       (rst_detall.Fields("videosub") = 0 And rst_detall.Fields("VideoSubPDeT") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("apnea") = 1 And rst_detall.Fields("APDET") = vbFalse) Or _
       (rst_detall.Fields("apnea") = 0 And rst_detall.Fields("APDET") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("nocmas") = 1 And rst_detall.Fields("NoCMASPDet") = vbFalse) Or _
       (rst_detall.Fields("nocmas") = 0 And rst_detall.Fields("NoCMASPDet") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    If (rst_detall.Fields("fusell") = 1 And rst_detall.Fields("F") = vbFalse) Or _
       (rst_detall.Fields("fusell") = 0 And rst_detall.Fields("F") = vbTrue) Then
        isllicenciachanged = True
        Exit Function
    End If
                            
    isllicenciachanged = False
End Function


Private Sub syncdadespersonals_Click()
    Dim actualitzacio As String
    
    actualitzacio = msglastupdate("dadespersonals")
    
    If actualitzacio = "Exit" Then Exit Sub
    
    dadespersonals_action False, actualitzacio, Me![smsdadespersonalsweb], Me![resumdadespersonals], Me![tauladadespersonalsweb]
    
    actualitzacio = dataactualitzacio("dadespersonals")
    dadespersonals_action True, actualitzacio, Me![smsdadespersonalsweb], Me![resumdadespersonals], Me![tauladadespersonalsweb]

End Sub


Private Sub dadespersonals_action(check As Boolean, actualitzacio As String, text As Label, Resum As Label, llista As ListBox)
On Error Resume Next
    
    Dim rst As Recordset
    Dim query As String, queryabm As String, error As String, dadespersonals_lu As String
    Dim j As Integer
    Dim DNI As Variant
    Dim updatedate As Boolean
    Dim syncok As Boolean
    
    syncok = True
    
    text.BorderStyle = 0
    llista.Visible = False
    
    ' Muntar taula no sincronitzats
    llista.RowSource = ""
    llista.ColumnCount = 4
    llista.ColumnHeads = True
    llista.AddItem "nom;cognoms;dni;accio"
    
    If actualitzacio = "" Then
    ' Totes les dades
        query = querydadespersonals
    Else
    ' Query simplificada per buscar altes, baixes i modificacions a Web que no estan a ACCESS des de última actualització
    ' Des de darrera actualització
        query = "SELECT m_persones.*, NULL AS DNI FROM m_persones "
        query = query & " WHERE m_persones.datamodificacio > #" & Format(actualitzacio, "yyyy-mm-dd hh:mm:ss") & "#"
    End If
    
    Set rst = CurrentDb.OpenRecordset(query, dbOpenDynaset)
    If Err.Number > 0 Then
        error = "Error en la connexió a la base de dades "
        GoTo Errordb
    End If
   
    updatedate = True
    
    If Not (rst.EOF) Then
        rst.MoveFirst
        
        Do While Not rst.EOF
            If (actualitzacio = "") Then       ' Tot
                DNI = rst.Fields("datos personales.DNI")
            Else       ' Des de últim update
                DNI = DLookup("DNI", "datos personales", "dni = '" & rst.Fields("m_persones.dni") & "'") ' dni
            End If
                
            If IsNull(DNI) Then  ' No existeix a ACCESS
                If IsNull(rst.Fields("databaixa")) Then   'Sense Data baixa
                    ' Donar alta
                    syncok = False
                    If Not check Then
                        If isdatadadespersonalsok(rst) Then
                            queryabm = queryinsertdadespersonals(rst)
                            CurrentDb.Execute queryabm
        
                            If Err.Number > 0 Then
                                logerror_db Err.Number, Err.Description, "Error modificació dades personals web -> Access", "m_sincronitzacio", "dadespersonals_action", queryabm
                                Err.Clear
                            End If
                        Else
                            updatedate = False
                            error = "Dades personals ('" & rst.Fields("nom") & "', '" & rst.Fields("cognoms") & "', '" _
                                    & rst.Fields("m_persones.dni") & "') amb dades incorrectes (Alta)"
                            logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "dadespersonals_action", ""
                            Err.Clear
                        End If
                    Else
                        llista.AddItem rst.Fields("nom") & ";" & rst.Fields("cognoms") _
                                    & ";" & rst.Fields("m_persones.dni") & "; Alta "
                    End If
                Else
                    ' Nothing to do
                End If
            Else    ' Existeix a ACCESS
                syncok = False
                If IsNull(rst.Fields("databaixa")) Then   'Sense Data baixa
                    ' Modificar dades existents a ACCESS amb les dades del Web
                    If Not check Then
                        If isdatadadespersonalsok(rst) Then
                            queryabm = queryupdatedadespersonals(rst, DNI)
                            CurrentDb.Execute queryabm
        
                            If Err.Number > 0 Then
                                logerror_db Err.Number, Err.Description, "Error modificació dades personals web -> Access", "m_sincronitzacio", "dadespersonals_action", queryabm
                                Err.Clear
                            End If
                        Else
                            updatedate = False
                            error = "Dades personals ('" & rst.Fields("nom") & "', '" & rst.Fields("cognoms") & "', '" _
                                    & rst.Fields("m_persones.dni") & "') amb dades incorrectes (Modificació)"
                            logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "dadespersonals_action", ""
                            Err.Clear
                        End If
                    Else
                        llista.AddItem rst.Fields("nom") & ";" & rst.Fields("cognoms") _
                                    & ";" & rst.Fields("m_persones.dni") & "; Modificació "
                    End If
                Else
                    ' Donar de baixa
                    If Not check Then
                        If isdatadadespersonalsok(rst) Then
                            queryabm = querydeletedadespersonals(DNI)
                            CurrentDb.Execute queryabm
        
                            If Err.Number > 0 Then
                                logerror_db Err.Number, Err.Description, "Error modificació dades personals web -> Access", "m_sincronitzacio", "dadespersonals_action", queryabm
                                Err.Clear
                            End If
                        Else
                            updatedate = False
                            error = "Dades personals ('" & rst.Fields("nom") & "', '" & rst.Fields("cognoms") & "', '" _
                                    & rst.Fields("m_persones.dni") & "') amb dades incorrectes (baixa)"
                            logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "dadespersonals_action", ""
                            Err.Clear
                        End If
                    Else
                        llista.AddItem rst.Fields("nom") & ";" & rst.Fields("cognoms") _
                                      & ";" & rst.Fields("m_persones.dni") & "; Baixa "
                    End If
                End If
                
            End If
            rst.MoveNext
        Loop
    End If
    
    If Not rst Is Nothing Then
        rst.Close
        Set rst = Nothing
    End If
    
    If Err.Number > 0 Then
        error = "Error"
        query = ""
        GoTo Errordb
    End If

    If check Then  ' Només comprovant
        If syncok Then
            text.Caption = "Taula Dades Personals Web -> Local Sincronitzada correctament"
            text.ForeColor = ok_color
            Resum.Caption = "Taula Dades Personals Web -> Local Sincronitzada correctament"
            Resum.ForeColor = ok_color
        Else
            text.Caption = "Les següents dades a la taula Dades Personals Web -> Local" _
                & ", no estan sincronitzades amb l'aplicació web"
            text.ForeColor = nosync_color
            Resum.Caption = "Cal sincronitzar la taula Dades Personals Web -> Local"
            Resum.ForeColor = nosync_color
            llista.Visible = True
        End If
    Else    ' Fent sync
        ' si tot ok, modificar data última actualització
        If updatedate Then
            query = "UPDATE m_lastupdate SET lastupdate = now WHERE taula = 'dadespersonals'"
            CurrentDb.Execute query
            If Err.Number > 0 Then
                error = "Error actualitzant data de sincronització"
                GoTo Errordb
            End If
            dadespersonals_lu = updatedataactualitzacio("dadespersonals")
            Me![smslastupdatedadespersonals].Caption = dadespersonals_lu
        End If
    End If
    
Exit_dadespersonals_action:
    Exit Sub

Errordb:
    If Not check Then
        MsgBox error & vbCrLf & Err.Description
    Else
        text.Caption = Err.Description & vbCrLf & error
        text.ForeColor = error_color
        Resum.Caption = Err.Description & vbCrLf & error
        Resum.ForeColor = error_color
        llista.Visible = False
    End If
    logerror_db Err.Number, Err.Description, error & vbCrLf & Err.Description, "m_sincronitzacio", "dadespersonals_action", query
End Sub

Private Function isdatadadespersonalsok(rst As Recordset) As Boolean
    ' Telefons, mail i comarca poden ser null
    If (IsNull(rst.Fields("nom")) Or rst.Fields("nom") = "" Or _
        IsNull(rst.Fields("cognoms")) Or rst.Fields("cognoms") = "" Or _
        IsNull(rst.Fields("m_persones.dni")) Or rst.Fields("m_persones.dni") = "" Or _
        IsNull(rst.Fields("datanaixement")) Or _
        IsNull(rst.Fields("sexe")) Or rst.Fields("sexe") = "" Or _
        IsNull(rst.Fields("addradreca")) Or rst.Fields("addradreca") = "" Or _
        IsNull(rst.Fields("addrpob")) Or rst.Fields("addrpob") = "" Or _
        IsNull(rst.Fields("addrcp")) Or rst.Fields("addrcp") = "" Or _
        IsNull(rst.Fields("addrprovincia")) Or rst.Fields("addrprovincia") = "" Or _
        IsNull(rst.Fields("addrnacionalitat")) Or rst.Fields("addrnacionalitat") = "") Then
        isdatadadespersonalsok = False
    Else
        isdatadadespersonalsok = True
    End If
End Function


Private Function querydeletedadespersonals(DNI As Variant) As String
    Dim query As String

    query = "DELETE FROM [datos personales] "
    query = query & " WHERE [datos personales].DNI = '" & DNI & "'"
    
    querydeletedadespersonals = query
End Function

Private Function queryupdatedadespersonals(rst As Recordset, DNI As Variant) As String
    Dim query As String

    query = "UPDATE [datos personales] SET "
    
    query = query & " [datos personales].nom = '" & Replace(rst.Fields("nom"), "'", "''") & "',"
    query = query & " [datos personales].cognoms = '" & Replace(rst.Fields("cognoms"), "'", "''") & "',"
    query = query & " [datos personales].dn = '" & rst.Fields("datanaixement") & "',"
    query = query & " [datos personales].SEXO = '" & rst.Fields("sexe") & "',"
    If IsNull(rst.Fields("telefon1")) Then
        query = query & " [datos personales].telf = NULL,"
    Else
        query = query & " [datos personales].telf = " & rst.Fields("telefon1") & ","
    End If
    If IsNull(rst.Fields("telefon2")) Then
        query = query & " [datos personales].telef2dp = NULL,"
    Else
        query = query & " [datos personales].telef2dp = " & rst.Fields("telefon2") & ","
    End If
    If IsNull(rst.Fields("mail")) Then
        query = query & " [datos personales].mail = NULL,"
    Else
        query = query & " [datos personales].mail = '" & rst.Fields("mail") & "',"
    End If
    query = query & " [datos personales].dir = '" & Replace(rst.Fields("addradreca"), "'", "''") & "',"
    query = query & " [datos personales].pob = '" & Replace(rst.Fields("addrpob"), "'", "''") & "',"
    query = query & " [datos personales].cpDp = '" & rst.Fields("addrcp") & "',"
    query = query & " [datos personales].provDP = '" & rst.Fields("addrprovincia") & "',"
    If IsNull(rst.Fields("addrcomarca")) Then
        query = query & " [datos personales].Comarca_Dp = NULL,"
    Else
        query = query & " [datos personales].Comarca_Dp = '" & Replace(rst.Fields("addrcomarca"), "'", "''") & "',"
    End If
    query = query & " [datos personales].nacionalidad = '" & rst.Fields("addrnacionalitat") & "',"
    If IsNull(rst.Fields("club")) Then
        query = query & " [datos personales].fedeclubDP = NULL"
    Else
        query = query & " [datos personales].fedeclubDP = '" & rst.Fields("club") & "'"
    End If
    
    query = query & " WHERE [datos personales].DNI = '" & DNI & "'"
    
    queryupdatedadespersonals = query
End Function

Private Function queryinsertdadespersonals(rst As Recordset) As String
    Dim query As String

    query = "INSERT INTO [datos personales] (nom, cognoms, DNI, dn, SEXO, " _
                          & "telf, telef2dp, mail, dir, pob, cpDp, " _
                          & "provDP, Comarca_Dp, nacionalidad, fedeclubDP, FechaEntradaDp) VALUES ("
                     
    query = query & "'" & Replace(rst.Fields("nom"), "'", "''") & "', "
    query = query & "'" & Replace(rst.Fields("cognoms"), "'", "''") & "', "
    query = query & "'" & rst.Fields("m_persones.DNI") & "', "
    query = query & "'" & rst.Fields("datanaixement") & "', "
    query = query & "'" & rst.Fields("sexe") & "', "
    If IsNull(rst.Fields("telefon1")) Then
        query = query & " NULL, "
    Else
        query = query & rst.Fields("telefon1") & ", "
    End If
    If IsNull(rst.Fields("telefon2")) Then
        query = query & " NULL, "
    Else
        query = query & rst.Fields("telefon2") & ", "
    End If
    If IsNull(rst.Fields("mail")) Then
        query = query & " NULL, "
    Else
        query = query & "'" & rst.Fields("mail") & "', "
    End If
    query = query & "'" & Replace(rst.Fields("addradreca"), "'", "''") & "', "
    query = query & "'" & Replace(rst.Fields("addrpob"), "'", "''") & "', "
    query = query & "'" & rst.Fields("addrcp") & "', "
    query = query & "'" & rst.Fields("addrprovincia") & "', "
    If IsNull(rst.Fields("addrcomarca")) Then
        query = query & " NULL, "
    Else
        query = query & "'" & Replace(rst.Fields("addrcomarca"), "'", "''") & "', "
    End If
    query = query & "'" & rst.Fields("addrnacionalitat") & "', "
    If IsNull(rst.Fields("club")) Then
        query = query & " NULL, "
    Else
        query = query & "'" & rst.Fields("club") & "', "
    End If
    query = query & "'" & Now & "'"
    query = query & ")"

    queryinsertdadespersonals = query
End Function

Private Sub syncclubs_Click()
On Error Resume Next
    
    Dim rst As Recordset
    Dim query As String, error As String
        
    Set rst = CurrentDb.OpenRecordset(queryclubs, dbOpenDynaset)
    If Err.Number > 0 Then
        error = "Error en la connexió a la base de dades "
        GoTo Errordb
    End If
   
    If Not (rst.EOF) Then
        rst.MoveFirst
        
        Do While Not rst.EOF
            If (IsNull(rst.Fields(0)) Or rst.Fields(0) = "" Or _
                IsNull(rst.Fields(1)) Or rst.Fields(1) < 0 Or _
                IsNull(rst.Fields(2)) Or rst.Fields(2) = "" Or _
                rst.Fields(3) < 0 Or _
                IsNull(rst.Fields(6)) Or rst.Fields(6) = "") Then
                ' Telefon pot ser null
                
                error = "Club ('" & rst.Fields(0) & "', " & rst.Fields(1) & ", '" _
                      & rst.Fields(2) & "', " & rst.Fields(3) & ", '" & rst.Fields(4) & "') amb dades incorrectes"
                logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "syncclubs_Click", ""
                Err.Clear
            Else
            
                query = "INSERT INTO m_clubs (codi, tipus, nom, telefon, mail, web, cif, addradreca, addrpob, addrcp, addrprovincia) "
                query = query & "VALUES ('" & rst.Fields(0) & "', " & rst.Fields(1) & ", '" & rst.Fields(2) & "'"
                If IsNull(rst.Fields(3)) Then
                    query = query & ", NULL, "
                Else
                    query = query & ", " & rst.Fields(3) & ", "
                End If
                If IsNull(rst.Fields(4)) Or rst.Fields(4) = "" Then
                    query = query & ", NULL, "
                Else
                    query = query & ", '" & rst.Fields(4) & "', "
                End If
                If IsNull(rst.Fields(5)) Or rst.Fields(5) = "" Then
                    query = query & ", NULL, "
                Else
                    query = query & ", '" & rst.Fields(5) & "', "
                End If
                query = query & ", '" & rst.Fields(6) & "', "
                If IsNull(rst.Fields(7)) Or rst.Fields(7) = "" Then
                    query = query & ", NULL, "
                Else
                    query = query & ", '" & rst.Fields(7) & "', "
                End If
                If IsNull(rst.Fields(8)) Or rst.Fields(8) = "" Then
                    query = query & ", NULL, "
                Else
                    query = query & ", '" & rst.Fields(8) & "', "
                End If
                If IsNull(rst.Fields(9)) Or rst.Fields(9) = "" Then
                    query = query & ", NULL, "
                Else
                    query = query & ", '" & rst.Fields(9) & "', "
                End If
                If IsNull(rst.Fields(10)) Or rst.Fields(10) = "" Then
                    query = query & ", NULL) "
                Else
                    query = query & ", '" & rst.Fields(10) & "') "
                End If
                
                CurrentDb.Execute query
        
                If Err.Number > 0 Then
                    error = "Error en insertar registre "
                    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "syncclubs_Click", query
                    Err.Clear
                End If
            End If
            rst.MoveNext
        Loop
    End If
    
    If Not rst Is Nothing Then
        rst.Close
        Set rst = Nothing
    End If
    
    If Err.Number > 0 Then
        error = "Error"
        query = ""
        GoTo Errordb
    End If
    checkclubs_Click

Exit_syncclubs_Click:
    Exit Sub

Errordb:
    MsgBox error & vbCrLf & Err.Description
    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "syncclubs_Click", query
End Sub


Private Sub synctipusclub_Click()
On Error Resume Next
    
    Dim rst As Recordset
    Dim query As String, error As String
    
    Set rst = CurrentDb.OpenRecordset(querytipusclub, dbOpenDynaset)
    If Err.Number > 0 Then
        error = "Error en la connexió a la base de dades "
        GoTo Errordb
    End If
   
    If Not (rst.EOF) Then
        rst.MoveFirst
        
        Do While Not rst.EOF
            If (IsNull(rst.Fields(0)) Or rst.Fields(0) < 0 Or _
                IsNull(rst.Fields(1)) Or rst.Fields(1) = "") Then
                
                error = "Tipus Club (" & rst.Fields(0) & ", '" & rst.Fields(1) & "') amb dades incorrectes"
                logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synctipusclub_Click", ""
                Err.Clear
            Else
                query = "INSERT INTO m_tipusclub (id, tipus) " _
                      & "VALUES (" & rst.Fields(0) & ", '" & rst.Fields(1) & "')"
                
                CurrentDb.Execute query
        
                If Err.Number > 0 Then
                    error = "Error en insertar registre "
                    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synctipusclub_Click", query
                    Err.Clear
                End If
            End If
            rst.MoveNext
        Loop
    End If
    
    If Not rst Is Nothing Then
        rst.Close
        Set rst = Nothing
    End If
    
    If Err.Number > 0 Then
        error = "Error"
        query = ""
        GoTo Errordb
    End If
    checktipusclub_Click

Exit_synctipusclub_Click:
    Exit Sub

Errordb:
    MsgBox error & vbCrLf & Err.Description
    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synctipusclub_Click", query
End Sub

Private Sub synctipusparte_Click()
On Error Resume Next
    
    Dim rst As Recordset
    Dim query As String, error As String
    
    Set rst = CurrentDb.OpenRecordset(querytipusparte, dbOpenDynaset)
    If Err.Number > 0 Then
        error = "Error en la connexió a la base de dades "
        GoTo Errordb
    End If
   
    If Not (rst.EOF) Then
        rst.MoveFirst
        
        Do While Not rst.EOF
            If (IsNull(rst.Fields(0)) Or rst.Fields(0) < 0 Or _
                IsNull(rst.Fields(1)) Or rst.Fields(1) = "" Or _
                IsNull(rst.Fields(2)) Or rst.Fields(2) = "") Then
                error = "Tipus Parte (" & rst.Fields(0) & ", '" & rst.Fields(1) & "', '" _
                      & rst.Fields(2) & "') amb dades incorrectes"
                logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synctipusparte_Click", ""
                Err.Clear
            Else
                query = "INSERT INTO m_tipusparte (id, codi, descripcio) " _
                      & "VALUES (" & rst.Fields(0) & ", '" & rst.Fields(1) & "', '" _
                      & rst.Fields(2) & "')"
                
                CurrentDb.Execute query
        
                If Err.Number > 0 Then
                    error = "Error en insertar registre "
                    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synctipusparte_Click", query
                    Err.Clear
                End If
            End If
            rst.MoveNext
        Loop
    End If
    
    If Not rst Is Nothing Then
        rst.Close
        Set rst = Nothing
    End If
    
    If Err.Number > 0 Then
        error = "Error"
        query = ""
        GoTo Errordb
    End If
    checktipusparte_Click

Exit_synctipusparte_Click:
    Exit Sub

Errordb:
    MsgBox error & vbCrLf & Err.Description
    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synctipusparte_Click", query
End Sub

Private Sub synccategories_Click()
On Error Resume Next
    
    Dim rst As Recordset
    Dim query As String, error As String
        
    Set rst = CurrentDb.OpenRecordset(querycategories, dbOpenDynaset)
    If Err.Number > 0 Then
        error = "Error en la connexió a la base de dades "
        GoTo Errordb
    End If
   
    If Not (rst.EOF) Then
        rst.MoveFirst
        
        Do While Not rst.EOF
            If (IsNull(rst.Fields(0)) Or rst.Fields(0) = 0 Or _
                IsNull(rst.Fields(1)) Or rst.Fields(1) = 0 Or _
                IsNull(rst.Fields(2)) Or rst.Fields(2) = "" Or _
                IsNull(rst.Fields(3)) Or rst.Fields(3) = "") Then
                    
                error = "Categoria (" & rst.Fields(0) & ", " & rst.Fields(1) & ", '" _
                      & rst.Fields(2) & "', '" & rst.Fields(3) & "') amb dades incorrectes"
                logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synccategories_Click", ""
                Err.Clear
            Else
                query = "INSERT INTO m_categories (codisortida, tipusparte, simbol, categoria) " _
                      & "VALUES (" & rst.Fields(0) & ", " & rst.Fields(1) & ", '" _
                      & rst.Fields(2) & "', '" & rst.Fields(3) & "')"
                
                CurrentDb.Execute query
        
                If Err.Number > 0 Then
                    error = "Error en insertar registre "
                    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synccategories_Click", query
                    Err.Clear
                End If
            End If
            rst.MoveNext
        Loop
    End If
    
    If Not rst Is Nothing Then
        rst.Close
        Set rst = Nothing
    End If
    
    If Err.Number > 0 Then
        error = "Error"
        query = ""
        GoTo Errordb
    End If
    
    checkcategories_Click

Exit_synccategories_Click:
    Exit Sub

Errordb:
    MsgBox error & vbCrLf & Err.Description
    logerror_db Err.Number, Err.Description, error, "m_sincronitzacio", "synccategories_Click", query
End Sub


Private Sub checktaula(query As String, taula As String, text As Label, Resum As Label, camps() As String, llista As ListBox)
On Error Resume Next
    
    Dim rst As Recordset
    Dim sms As String, error As String, llistarow As String
    Dim j As Integer
        
    text.BorderStyle = 0
    
    Set rst = CurrentDb.OpenRecordset(query, dbOpenDynaset)
    If Err.Number > 0 Then
        error = "Error en la connexió a la base de dades "
        GoTo Errordb
    End If
   
    If (rst.EOF) Then
        sms = "Taula " & taula & " Sincronitzada correctament"
        text.ForeColor = ok_color
        Resum.Caption = "Taula " & taula & " Sincronitzada correctament"
        Resum.ForeColor = ok_color
        llista.Visible = False
    Else
        llista.Visible = True
        rst.MoveFirst
        
        llista.RowSource = ""
        llista.ColumnCount = UBound(camps) + 1
        llista.ColumnHeads = True
        
        llistarow = camps(0)
        For j = LBound(camps) + 1 To UBound(camps)
            llistarow = llistarow & ";" & camps(j)
        Next j
        llista.AddItem llistarow
        
        sms = "Les següents dades a la taula " & taula & _
               ", no estan sincronitzades amb l'aplicació web"
        text.ForeColor = nosync_color
        
        Resum.Caption = "Cal sincronitzar la taula  " & taula
        Resum.ForeColor = nosync_color
        
        Do While Not rst.EOF
            llistarow = rst.Fields(0)
            For j = LBound(camps) + 1 To UBound(camps)
                llistarow = llistarow & ";" & rst.Fields(j)
            Next j
            llista.AddItem llistarow
            
            rst.MoveNext
        Loop
    End If
    'MsgBox sms
    
    If Not rst Is Nothing Then
        rst.Close
        Set rst = Nothing
    End If
    
    text.Caption = sms
    
    If Err.Number > 0 Then
        error = "Error"
        query = ""
        GoTo Errordb
    End If

Exit_checktipusclub_Click:
    Exit Sub

Errordb:
    'MsgBox "Error en la connexió a la base de dades"
    text.Caption = Err.Description & vbCrLf & error
    text.ForeColor = error_color
    Resum.Caption = Err.Description & vbCrLf & error
    Resum.ForeColor = error_color
    llista.Visible = False
    logerror_db Err.Number, Err.Description, error & " taula " & taula, "m_sincronitzacio", "checktipusclub_Click", query
End Sub


