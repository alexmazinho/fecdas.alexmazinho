**************************************************************************************************
*********************************           Funcionalitat        ********************************* 
**************************************************************************************************

num factura (memcol)  000XX/2012
	Club CAT998 (Independent)  --> Missatge "la factura debe ser modificada con los datos del cliente"
 
Tipus de client
1 --> Club CAT998 (Independent) 
3 --> Resta

Data, nif, nom del club

Detall per a cada factura,
	Línies, obté el "codigosalida" segons tipus parte, i la quantitat
	Línia directius, tècnics, aficionats,aficionatsNF,seniors, extrangers, juvenils, infantils
	Obté el preu
		Club CAT998 (Independent)	--> Precioparticularproducto
		Resta						--> PrecioClubproducto

Error no troba "salida" ni "salida detalle" !!!!!


**************************************************************************************************
*********************************               BBDD             ********************************* 
**************************************************************************************************

// Generate getter & setters: php app/console doctrine:generate:entities Fecdas
// Create tables: php app/console doctrine:schema:create  [--dump-sql]
// Actualitzacions: php app/console doctrine:schema:update --force
// A producció: sudo -u www-data php app/check.php
//				sudo -u www-data php app/console doctrine:ensure-production-settings --no-debug
//              sudo -u www-data php app/console cache:clear --env=prod --no-debug
//				Prova esborrar i regenerar amb warm
//				sudo -u www-data php app/console cache:warmup --env=prod
//http://grokbase.com/t/gg/symfony-es/131y3f9eg5/no-se-generan-archivos-proxy-php-en-cache-de-prod
//  			(No cal??!?!?) esborrar carpeta prod del cache on-line
//				Accedir al web per regenerar caché			
//				(No cal??!?!?)copiar els proxies de doctrine/orm des de local				
// insert into m_users values ('alexmazinho@gmail.com', 'CAT999', SHA1('mazinho'),'admin',0);
// insert into m_users values ('amacia22@xtec.cat', 'CAT047', SHA1('mazinho'),'user',0);
// insert into m_users values ('inslec@gmail.com', 'CAT999', SHA1('marianao'),'admin',0);
// insert into m_users values ('adminprova@fecdasgestio.cat', 'CAT999', SHA1('fecdas2012'),'admin',0);
// insert into m_users values ('userprova@fecdasgestio.cat', 'CAT047', SHA1('fecdas2012'),'user',0);


// Backup 
GRANT SELECT ON fecdas.* TO 'backupfecdas'@'localhost' IDENTIFIED BY '***'
WITH MAX_QUERIES_PER_HOUR 0
MAX_CONNECTIONS_PER_HOUR 0
MAX_UPDATES_PER_HOUR 0
MAX_USER_CONNECTIONS 0 ; 

// NO es pot crear usuaris en producció. Fer des de nominalia
GRANT SELECT ON fecdasgestio_cat_fecdas.* TO 'LMP156_backup'@'%' IDENTIFIED BY 'fecdas2013'
WITH MAX_QUERIES_PER_HOUR 0
MAX_CONNECTIONS_PER_HOUR 0
MAX_UPDATES_PER_HOUR 0
MAX_USER_CONNECTIONS 0 ; 


// http://www.howtoforge.com/adding-an-odbc-driver-for-mysql-on-ubuntu
// Configuració : iodbcadm-gtk
// DSN			fecdas_mysql 
// database 	fecdas
// password		mazinho
// server		localhost
// user			root

// XP --> Des de client Start, Control Panel --> Administrative Tools --> Data Sources (ODBC).
// Win7 --> cercar ODBC
// Driver http://www.mysql.com/products/connector/
// Baixar versió 5.1 mysql-connector-odbc-5.1.11-win32

// GRANT SELECT, UPDATE, INSERT, DELETE ON fecdas.* TO remoteadmin@'%' IDENTIFIED BY '2012$Fecdas';
// Dades Nominalia

// DSN			fecdas_mysql 
// database 	fecdasgestio_cat_fecdas	//fecdas
// password		2012fecdasWA		//2012$Fecdas
// server		hostingmysql264.nominalia.com	// IP
// user			LMP156_root		//remoteadmin

// Des de ACCESS Datos Externos --> Mas --> Base de Datos de ODBC
// Vincular --> Origen de datos del equipo


-- Tipus de clubs 

INSERT INTO m_tipusclub VALUES (0, 'Club');
INSERT INTO m_tipusclub VALUES (1, 'Centre Busseig');
INSERT INTO m_tipusclub VALUES (2, 'Centre i Club');
INSERT INTO m_tipusclub VALUES (3, 'Afiliat');
INSERT INTO m_tipusclub VALUES (4, 'Adherit');
INSERT INTO m_tipusclub VALUES (5, 'Centre Associat');
INSERT INTO m_tipusclub VALUES (6, 'Federació Autonòmica');
INSERT INTO m_tipusclub VALUES (7, 'Centre Col·laborador');

-- Tipus de partes 

INSERT INTO m_tipusparte VALUES (1, 'LL Fed Cla', 'Llicència Federativa Tipus A');
INSERT INTO m_tipusparte VALUES (2, 'Asseg', 'Llicència Federativa Tipus B');
INSERT INTO m_tipusparte VALUES (3, 'Natc Aletes 2005', 'Natació amb Aletes 2006');
INSERT INTO m_tipusparte VALUES (4, 'Natc Aletes 2006-7', 'Natació amb Aletes 2006-2007 Tipus C');
INSERT INTO m_tipusparte VALUES (5, 'LL Fed Red', 'Llicència Federativa Reduïda Tipus A');
INSERT INTO m_tipusparte VALUES (6, 'Asseg Red', 'Llicència Federativa Reduïda Tipus B');
INSERT INTO m_tipusparte VALUES (7, 'LL Fed Cla E', 'Llicència Federativa Tipus E');
INSERT INTO m_tipusparte VALUES (8, 'LL Fed Cla F', 'Llicència Federativa Tipus F');
	 	
-- Categories dins els tipus de partes 

INSERT INTO m_categories VALUES (7010000, 1, 'A', 'Aficionat', 'Llicència Aficionat (Habilitada per Competició)', 33.90);
INSERT INTO m_categories VALUES (7010001, 1, 'T', 'Tècnic','Llicència Tècnic (Habilitada per Competició)', 39.70);
INSERT INTO m_categories VALUES (7010002, 1, 'I', 'Infantil', 'Llicència Infantil (Habilitada per Competició)', 14.20);
INSERT INTO m_categories VALUES (7010003, 2, 'A', 'Aficionat', 'Llicència "Tipus B" Aficionat (No Competició)', 33.90);
INSERT INTO m_categories VALUES (7010004, 5, 'A', 'Aficionat', 'Llicència Aficionat Reduïda', 29.80);
INSERT INTO m_categories VALUES (7010005, 5, 'T', 'Tècnic', 'Llicència Tècnic Reduïda', 33.84);
INSERT INTO m_categories VALUES (7010006, 5, 'I', 'Infantil', 'Llicència Infantil Reduïda', 8.90);
INSERT INTO m_categories VALUES (7010007, 6, 'A', 'Aficionat', NULL, 0);
INSERT INTO m_categories VALUES (7010008, 3, 'A', 'Aficionat', 'Llicència Aficionat Nat. Aletes', 26.00);
INSERT INTO m_categories VALUES (7010009, 3, 'T', 'Tècnic', 'Llicència Tècnic Nat. Aletes', 31.00);
INSERT INTO m_categories VALUES (7010010, 3, 'I', 'Infantil', 'Llicència Infantil Nat. Aletes', 9.00);
INSERT INTO m_categories VALUES (7010011, 4, 'A', 'Aficionat', 'Llicència Aficionat Nat. Aletes 2010-2011', 32.45);
INSERT INTO m_categories VALUES (7010012, 4, 'T', 'Tècnic', 'Llicència Tècnic Nat. Aletes 2010-2011', 36.30);
INSERT INTO m_categories VALUES (7010013, 4, 'I', 'Infantil', 'Llicència Infantil Nat. Aletes 2010-2011', 12.30);
INSERT INTO m_categories VALUES (7010015, 7, 'A', 'Aficionat', 'LL Fed Cla E  aficionat', 38.10);
INSERT INTO m_categories VALUES (7010016, 7, 'I', 'Infantil', 'LL Fed Cla E Infantil', 17.10);
INSERT INTO m_categories VALUES (7010017, 7, 'T', 'Tècnic', 'LL Fed Cla E  Tècnic', 43.60);
INSERT INTO m_categories VALUES (7010018, NULL, NULL, NULL, 'Llicencia Internacional CMAS', 21.50);
INSERT INTO m_categories VALUES (7010019, 8, 'A', 'Aficionat', 'LL Fed Cla F  aficionat', 39.00);
INSERT INTO m_categories VALUES (7010020, 8, 'I', 'Infantil', 'LL Fed Cla F  Infantil', 0);
INSERT INTO m_categories VALUES (7010021, 8, 'T', 'Tècnic', 'LL Fed Cla F Tècnic', 0);



-- Pasos per a l'exportació
-- Crear la consulta
-- Exportar a arxiu de text
-- Sense marcar format i disseny
-- Delimitat, delimitador ';' i qualificar de text ", ordre de les dates AMD delimitador '-', símbol decimal ','
-- Carregar dades a MySQL (LOAD DATA LOCAL INFILE...)

-- Dades personals  (consulta m_fecdas_dadespersones)
-- SELECT [datos personales].nom, [datos personales].cognoms, [datos personales].DNI, [datos personales].dn, [datos personales].SEXO, nz( iif([datos personales].telf = 0, NULL, [datos personales].telf), "\N"), nz( iif([datos personales].telef2dp = 0, NULL,  [datos personales].telef2dp), "\N"), nz([datos personales].mail,"\N"), [datos personales].dir, [datos personales].pob, [datos personales].cpDp, [datos personales].provDP, nz([datos personales].Comarca_Dp,"\N"), [datos personales].nacionalidad FROM [datos personales];

LOAD DATA LOCAL INFILE '/home/alex/Escriptori/VirtualBoxFolder/m_fecdas_dadespersones.txt' INTO TABLE m_persones FIELDS TERMINATED BY ';'  OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\r\n' (`nom`, `cognoms`, `DNI`, `datanaixement`,`sexe`,`telefon1`,`telefon2`,`mail`,`addradreca`,`addrpob`, `addrcp`, `addrprovincia`, `addrcomarca`, `addrnacionalitat`)

Canvi Provincies


SELECT * FROM m_persones WHERE addrprovincia NOT IN (SELECT DISTINCT provincia FROM m_municipis) 
SELECT * FROM m_persones WHERE addrprovincia NOT IN ('Girona', 'Lleida', 'Barcelona', 'Tarragona')

UPDATE m_persones SET addrprovincia = 'Barcelona' WHERE addrprovincia = 'BARCELONA';
UPDATE m_persones SET addrprovincia = 'Girona' WHERE addrprovincia = 'GIRONA';
UPDATE m_persones SET addrprovincia = 'Lleida' WHERE addrprovincia = 'LLEIDA';
UPDATE m_persones SET addrprovincia = 'Tarragona' WHERE addrprovincia = 'TARRAGONA';

Data entrada i modificació

UPDATE m_persones SET dataentrada = NOW(), datamodificacio = NOW();

Posar Validat a true

UPDATE m_persones SET validat = 1;
UPDATE m_persones SET web = 0;


Comprovar que tots els DNI's són numèrics!!!!!!

select * from m_persones where dni NOT REGEXP '^-?[0-9]+$';

-- Clubs (consulta m_fecdas_clubs)

SELECT clubs.fedeclub, 
clubs.[N CLUB C], 
clubs.TipoClub, 
nz(clubs.[TELÉFONO CLUB],"\N"), 
nz(clubs.[NIF CLUB], "\N"),
nz(clubs.[DIRECCIÓN CLUB], "\N"), 
nz(clubs.[CP CLUB], "\N"),
nz(clubs.[POBLACIÓN CLUB], "\N"), 
nz(clubs.[PROVINCIA CLUB], "\N"), 
nz(clubs.email, "\N"), 
nz(clubs.WebClub, "\N")
FROM clubs;


LOAD DATA LOCAL INFILE '/home/alex/Escriptori/VirtualBoxFolder/m_fecdas_clubs.txt' 
INTO TABLE m_clubs FIELDS TERMINATED BY ';'  OPTIONALLY ENCLOSED BY '"' 
LINES TERMINATED BY '\r\n' (`codi`,`nom`,`tipus`, `telefon`, `cif`, addradreca, addrcp, addrpob, addrprovincia, mail, web)



-- Partes (consulta m_fecdas_partes)
-- NO --> SELECT Format([nº de relación],"#") AS Expr2, Format([fecha de altaParte],"yyyy-mm-dd") AS Expr1, Format([Fecha de entrada],"yyyy-mm-dd") AS Expr3, Format([partefacturado],"yyyy-mm-dd") AS Expr4, parte.pafed, parte.fedeclubParte 
-- FROM parte  WHERE parte.pafed BETWEEN 1 AND 8 ORDER BY [fecha de altaParte];
-- SELECT Format([nº de relación],"#") AS Expr2, IIf(IsNull([fecha de altaParte]),"\N",Format([fecha de altaParte],"yyyy-mm-dd")) AS Expr1, IIf(IsNull([Fecha de entrada]),"\N",Format([Fecha de entrada],"yyyy-mm-dd")) AS Expr3, IIf(IsNull([partefacturado]),"\N",Format([partefacturado],"yyyy-mm-dd")) AS Expr4, IIf(IsNull([partefacturado]),"\N",Format([partefacturado],"yyyy-mm-dd")) AS Expr5, IIf(parte.pafed = 3, 4, parte.pafed), parte.fedeclubParte, parte.idParte
FROM parte
WHERE (parte.pafed Between 1 And 8) AND parte.pafed <> 6 AND fedeclubParte IS NOT NULL
AND [fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012)
ORDER BY parte.[fecha de altaParte];

He canviat tipus parte 3 per 4 (Natació amb aletes)

-- Comprovar si hi ha partes diferents de tipus 1 a 8 
SELECT * FROM parte WHERE NOT parte.pafed Between 1 And 8 

-- Comprovar partes sense Club
SELECT * FROM parte WHERE fedeclubParte IS NULL
AND [fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012)

!!! Hi ha un {5691683B-C891-416A-9AA9-BBF4325B5901} !!!

-- Validar Partes amb tipus = 6 (Obsolet)
SELECT * FROM parte WHERE parte.pafed = 6
AND [fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012) 
 
!!! Hi ha 3 de setembre de 2011, caducats !!!

-- Comprovar partes tipus 3 (Natació amb aletes)
SELECT * FROM parte WHERE parte.pafed = 3
AND [fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012)

!!! No hi ha cap !!! 

LOAD DATA LOCAL INFILE '/home/alex/Escriptori/VirtualBoxFolder/m_fecdas_partes.txt' 
INTO TABLE m_partes FIELDS TERMINATED BY ';'  
OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\r\n' 
(`numrelacio`, `dataalta`,`dataentrada`,`datapagament`, `datafacturacio`, `tipus`, `club`,`idparte_access`)


Data modificació

UPDATE m_partes SET datamodificacio = NOW();

Check web

UPDATE m_partes SET web = 0;

-- Llicencies (m_fecdas_llicencies)

SELECT [parte detallado].idPartedet, [parte detallado].dniPDet, [parte detallado].[id de parte], categorias.códigosalidacategoria, [parte detallado].FechaEntradaPDet,
[parte detallado].PescaPDet, [parte detallado].EscafandrismoPDet, [parte detallado].NataciónPDet, [parte detallado].OrientaciónPDet, 
[parte detallado].BiologíaPDet, [parte detallado].[Foto-cinePDet], [parte detallado].hockeyPDet, 
[parte detallado].NoCMASPDet, [parte detallado].FSAPDET, [parte detallado].APDET, [parte detallado].VideoSubPDeT, 
[parte detallado].DataCaducitatPDET, [parte detallado].[365], [parte detallado].F
FROM (parte INNER JOIN [parte detallado] ON parte.idParte = [parte detallado].[id de parte]) INNER JOIN categorias ON parte.pafed = categorias.tipoparte
WHERE ((([parte detallado].categoriaParteDet )=[categorias].[IdCategoria])) AND
(parte.pafed Between 1 And 8) AND parte.pafed <> 6 AND fedeclubParte IS NOT NULL
AND [fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012)
ORDER BY parte.idParte, [parte detallado].idPartedet;


-- Comprovar que totes llicències pertànyen a un parte 

SELECT *  
FROM parte LEFT OUTER JOIN [parte detallado] ON [parte detallado].[id de parte] = parte.idParte 
WHERE [parte detallado].[id de parte] IS NULL AND
(parte.pafed Between 1 And 8) AND parte.pafed <> 6 AND fedeclubParte IS NOT NULL AND 
[fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012)

!!! HI ha un del CAT044 {8DF191D8-A8E9-40FA-AEC1-B3B97D7FF46E} !!!


-- Comprovar totes les llicències tenen categoria correcte
SELECT [parte detallado].*
FROM parte INNER JOIN [parte detallado] ON parte.idParte = [parte detallado].[id de parte] 
WHERE ([parte detallado].categoriaParteDet NOT IN (SELECT  [categorias].[IdCategoria] FROM categorias WHERE parte.pafed = categorias.tipoparte)
OR [parte detallado].categoriaParteDet IS NULL) AND
(parte.pafed Between 1 And 8) AND parte.pafed <> 6 AND fedeclubParte IS NOT NULL
AND [fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012)
ORDER BY [parte detallado].idPartedet;

!!! Hi ha una que no té x3299406x {5FE6F1DA-6851-4A74-AC8D-FF05E58A9D91} !!! 


-- Comprovar que totes les llicències tenen data caducitat
SELECT *  
FROM parte INNER JOIN [parte detallado] ON [parte detallado].[id de parte] = parte.idParte 
WHERE [parte detallado].DataCaducitatPDET IS NULL AND
(parte.pafed Between 1 And 8) AND parte.pafed <> 6 AND fedeclubParte IS NOT NULL AND 
[fecha de altaParte] IS NOT NULL AND 
((year([fecha de altaParte]) = 2011 AND month ([fecha de altaParte]) >= 09) OR
 year([fecha de altaParte]) >= 2012)

!!! Hi ha una CAT501  {00B8ECBE-A6F9-4CB6-A891-BA7666987253} 447033, és tipus parte 1 posar a 31/12/2012  !!!

-- EXportació 1 - actiu , 0 - inactiu 

Taula temporal

CREATE TABLE `fecdas`.`m_temp` (
`id`  INT(11) NOT NULL,
`DNI` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL ,
`idparte_access` VARCHAR( 38 ) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL ,
`codisortida` INT NOT NULL ,
`dataentrada` DATETIME,
`bpesca` BOOLEAN NOT NULL ,
`bescafa` BOOLEAN NOT NULL ,
`bnata` BOOLEAN NOT NULL ,
`borienta` BOOLEAN NOT NULL ,
`bbiolo` BOOLEAN NOT NULL ,
`bfoto` BOOLEAN NOT NULL ,
`bhockey` BOOLEAN NOT NULL ,
`bnocmas` BOOLEAN NOT NULL ,
`bfsap` BOOLEAN NOT NULL ,
`bap` BOOLEAN NOT NULL ,
`bvideo` BOOLEAN NOT NULL ,
`caducitat` DATETIME,
`b365` BOOLEAN NOT NULL ,
`bf` BOOLEAN NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;


LOAD DATA LOCAL INFILE '/home/alex/Escriptori/VirtualBoxFolder/m_fecdas_llicencies.txt' 
INTO TABLE m_temp FIELDS TERMINATED BY ';'  
OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\r\n' 


-- Verificar llicències natació amb aletes tipus 3 (7010008, 7010009, 7010010) 

SELECT * FROM `m_temp` WHERE `codisortida` between 7010008 and 7010010

!!! No hi ha cap !!!

Modificar categories natació amb aletes

UPDATE m_temp SET codisortida = 7010011 WHERE codisortida = 7010008;
UPDATE m_temp SET codisortida = 7010012 WHERE codisortida = 7010009;
UPDATE m_temp SET codisortida = 7010013 WHERE codisortida = 7010010;


/* Detectar llicencies sense persones. PEr exemple de persones no migrades  */
SELECT dni FROM `m_temp` WHERE dni not in (SELECT dni FROM m_persones) ORDER BY DNI




DELIMITER //

DROP PROCEDURE IF EXISTS  carregarLlicencies //

CREATE PROCEDURE carregarLlicencies()
BEGIN
	DECLARE final VARCHAR(5) DEFAULT 'START';
	DECLARE vid INT(11) DEFAULT -1;
	DECLARE vid_parte INT(11);
	DECLARE vid_persona INT(11);
	DECLARE i INT(11) DEFAULT -1;
	DECLARE vid_llicencia INT(11);
	DECLARE vDNI VARCHAR(20);
	DECLARE vidparte_access VARCHAR(38);
	DECLARE vid_partedetall INT(11);
	DECLARE vcodisortida INT(11);
	DECLARE vdataentrada, vcaducitat DATETIME;
	DECLARE vbpesca, vbescafa, vbnata, vborienta, vbbiolo, vbfoto, vbhockey,
			vbnocmas, vbfsap, vbap, vbvideo,	vb365, vbf TINYINT(1);
	DECLARE c_temp CURSOR FOR SELECT id, DNI, idparte_access, codisortida, dataentrada, bpesca, bescafa, 
									bnata, borienta, bbiolo, bfoto, bhockey, bnocmas,
									bfsap, bap, bvideo, caducitat, b365, bf FROM m_temp;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET final='END';
	OPEN c_temp;
	WHILE final <> 'END' DO
		 FETCH c_temp INTO vid_partedetall, vDNI, vidparte_access, vcodisortida, vdataentrada, vbpesca, vbescafa, 
		 					vbnata, vborienta, vbbiolo, vbfoto, vbhockey,vbnocmas, 
		 					vbfsap, vbap, vbvideo, vcaducitat, vb365, vbf;
		 IF final <> 'END' THEN
		 	SELECT id INTO vid FROM m_partes WHERE BINARY idparte_access = BINARY vidparte_access;
		 	IF vid <> -1 THEN
				SET vid_parte = vid;
			ELSE 
				SELECT CONCAT('Error, llicencia sense parte :', vidparte_access);
		 	END IF;
			SET vid = -1;
			SELECT COUNT(*) INTO i FROM m_persones WHERE DNI = vDNI;
			IF (i > 1) THEN 
				SELECT CONCAT('Error, persona varis clubs, dni :', vDNI, ' parte :', vidparte_access);
			ELSE
			 	SELECT id INTO vid FROM m_persones WHERE DNI = vDNI;
			 	IF vid <> -1 THEN
					SET vid_persona = vid;
					SELECT COUNT(*) INTO i FROM m_llicencies WHERE persona = vid_persona AND parte = vid_parte;
					IF (i > 0) THEN 
						SELECT CONCAT('Error, llicencia ja existeix, dni :', vDNI, ' parte :', vidparte_access);
					ELSE
						INSERT INTO m_llicencies (persona, parte, idparte_access, idpartedetall_access, categoria,
												pesca,escafandrisme,natacio,orientacio,biologia,
												fotocine,hockey,fotosubapnea,videosub,apnea,
												nocmas, fusell, datacaducitat, dataentrada) 
							VALUES (vid_persona, vid_parte, vidparte_access, vid_partedetall, vcodisortida,
									vbpesca, vbescafa, vbnata, vborienta, vbbiolo, 
									vbfoto, vbhockey, vbfsap, vbvideo, vbap, 
									vbnocmas, vbf, vcaducitat, vdataentrada);
					END IF;
				ELSE 
					SELECT CONCAT('Error, llicencia sense persona :', vDNI);
			 	END IF;
			 END IF;
		 END IF;
		 SET vid = -1;
	END WHILE;
	CLOSE c_temp;
END //

DELIMITER ;

CALL  carregarLlicencies();


DELIMITER //

DROP PROCEDURE IF EXISTS  carregarLlicencies_v2 //

CREATE PROCEDURE carregarLlicencies_v2()
BEGIN
	DECLARE final VARCHAR(5) DEFAULT 'START';
	DECLARE vid INT(11) DEFAULT -1;
	DECLARE vid_parte INT(11);
	DECLARE vclub_parte VARCHAR(6);
	DECLARE vclub_persona VARCHAR(6);
	DECLARE vid_persona INT(11);
	DECLARE i INT(11) DEFAULT -1;
	DECLARE vid_llicencia INT(11);
	DECLARE vDNI VARCHAR(20);
	DECLARE vidparte_access VARCHAR(38);
	DECLARE vid_partedetall INT(11);
	DECLARE vcodisortida INT(11);
	DECLARE vdataentrada, vcaducitat DATETIME;
	DECLARE vbpesca, vbescafa, vbnata, vborienta, vbbiolo, vbfoto, vbhockey,
			vbnocmas, vbfsap, vbap, vbvideo,	vb365, vbf TINYINT(1);
	DECLARE c_temp CURSOR FOR SELECT id, DNI, idparte_access, codisortida, dataentrada, bpesca, bescafa, 
									bnata, borienta, bbiolo, bfoto, bhockey, bnocmas,
									bfsap, bap, bvideo, caducitat, b365, bf FROM m_temp;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET final='END';
	OPEN c_temp;
	WHILE final <> 'END' DO
		 FETCH c_temp INTO vid_partedetall, vDNI, vidparte_access, vcodisortida, vdataentrada, vbpesca, vbescafa, 
		 					vbnata, vborienta, vbbiolo, vbfoto, vbhockey,vbnocmas, 
		 					vbfsap, vbap, vbvideo, vcaducitat, vb365, vbf;
		 IF final <> 'END' THEN
		 	SELECT id, club INTO vid, vclub_parte FROM m_partes WHERE BINARY idparte_access = BINARY vidparte_access;
		 	IF vid <> -1 THEN
				SET vid_parte = vid;
			ELSE 
				SELECT CONCAT('Error, llicencia sense parte :', vidparte_access);
		 	END IF;
			SET vid = -1;
			SELECT COUNT(*) INTO i FROM m_persones WHERE DNI = vDNI;
			IF (i > 1) THEN 
				SELECT COUNT(*) INTO i FROM m_persones WHERE DNI = vDNI AND club = vclub_parte;
				IF (i = 1) THEN
					SELECT id, club INTO vid, vclub_persona FROM m_persones WHERE DNI = vDNI AND club = vclub_parte;
				END IF;
			ELSE 
				SELECT id, club INTO vid, vclub_persona FROM m_persones WHERE DNI = vDNI;
			END IF;
			IF (i > 1) THEN
				SELECT CONCAT('Error, persona varis clubs, dni :', vDNI, ' parte :', vidparte_access);
			ELSE 
			 	IF vid <> -1 THEN
			 		IF (vclub_persona IS NULL) THEN
			 			SELECT CONCAT('Avis, persona sense club, dni :', vDNI);
			 			UPDATE m_persones SET club = vclub_parte WHERE DNI = vDNI; 
			 		END IF;
					SET vid_persona = vid;
					SELECT COUNT(*) INTO i FROM m_llicencies WHERE persona = vid_persona AND parte = vid_parte;
					IF (i > 0) THEN 
						SELECT CONCAT('Error, llicencia ja existeix, dni :', vDNI, ' parte :', vidparte_access);
					ELSE
						INSERT INTO m_llicencies (persona, parte, idparte_access, idpartedetall_access, categoria,
												pesca,escafandrisme,natacio,orientacio,biologia,
												fotocine,hockey,fotosubapnea,videosub,apnea,
												nocmas, fusell, datacaducitat, dataentrada) 
							VALUES (vid_persona, vid_parte, vidparte_access, vid_partedetall, vcodisortida,
									vbpesca, vbescafa, vbnata, vborienta, vbbiolo, 
									vbfoto, vbhockey, vbfsap, vbvideo, vbap, 
									vbnocmas, vbf, vcaducitat, vdataentrada);
					END IF;
				ELSE 
					SELECT CONCAT('Error, llicencia sense persona :', vDNI);
			 	END IF;
			 END IF;
		 END IF;
		 SET vid = -1;
	END WHILE;
	CLOSE c_temp;
END //

DELIMITER ;

CALL  carregarLlicencies_v2();


Data modificació

UPDATE m_llicencies SET datamodificacio = NOW();



--Verificador dels comptadors

SELECT SUM(bpesca), SUM(bescafa), SUM(bnata), SUM(borienta), SUM(bbiolo),
	SUM(bfoto), SUM(bhockey), SUM(bnocmas), SUM(bfsap), SUM(bap),
	SUM(bvideo), SUM(bf) FROM m_temp;
	
SELECT  SUM(pesca), SUM(escafandrisme), SUM(natacio), SUM(orientacio), SUM(biologia),
		SUM(fotocine), SUM(hockey), SUM(nocmas),  SUM(fotosubapnea), SUM(apnea),
		SUM(videosub), SUM(fusell) FROM m_llicencies;




Afegir el club de les persones

-- Comprovar persones amb llicència  (Han de coincidir)
SELECT * FROM m_persones WHERE id IN (SELECT persona FROM m_llicencies)

SELECT Count( DISTINCT persona ) FROM `m_llicencies` 


-- Esborrar persones sense llicència
DELETE FROM m_persones WHERE id NOT IN (SELECT persona FROM m_llicencies)


DELIMITER //

DROP PROCEDURE IF EXISTS  personaclub //

CREATE PROCEDURE personaclub() 
BEGIN
	DECLARE final VARCHAR(5) DEFAULT 'START';
	DECLARE ind INT DEFAULT 0;
	DECLARE comptador INT;
	DECLARE var_persona INT;
	DECLARE var_club VARCHAR(6);
	DECLARE c_persones CURSOR FOR SELECT id FROM m_persones;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET final='END';

	OPEN c_persones;
	WHILE final <> 'END' DO
		FETCH c_persones INTO var_persona;
		IF final <> 'END' THEN	
			SELECT COUNT(*) INTO comptador FROM 
						(SELECT p.club, count(*) FROM m_partes p 
						INNER JOIN m_llicencies l ON p.id = l.parte 
						WHERE l.persona = var_persona GROUP BY p.club) AS t;
			IF comptador <> 1 THEN
				IF comptador = 0 THEN 
					SELECT CONCAT('ERROR, persona amb cap llicència:', var_persona);
				ELSE
					SELECT CONCAT('ERROR, persona amb llicències a varis clubs:', var_persona);
				END IF;
			ELSE
				SELECT p.club INTO var_club FROM m_partes p INNER JOIN m_llicencies l ON p.id = l.parte 
						WHERE l.persona = var_persona GROUP BY p.club;
				UPDATE m_persones p SET p.club = var_club WHERE id = var_persona;
			END IF;
		END IF;
		SET ind = ind + 1;	
	END WHILE;
	CLOSE c_persones;
	SELECT CONCAT('TOTAL :', FORMAT(ind - 1,0));
END //

DELIMITER ;

CALL  personaclub();



Validar persones amb varis clubs


-- Persones amb més de una llicència al mateix club 
SELECT e.id FROM m_persones e INNER JOIN m_llicencies l ON e.id = l.persona 
INNER JOIN m_partes p ON p.id = l.parte GROUP BY p.club,e.id HAVING COUNT(*) > 1

Exporta mysql csv

-- Persones amb més d'un parte
SELECT e.id FROM m_persones e INNER JOIN m_llicencies l ON e.id = l.persona 
INNER JOIN m_partes p ON p.id = l.parte GROUP BY e.id HAVING COUNT(*) > 1

Exporta mysql csv

SELECT count(*), p.club  FROM m_partes p INNER JOIN m_llicencies l ON p.id = l.parte 
			WHERE l.persona = ??? GROUP BY p.club;

!!! 51 persones amb dades a varis clubs !!!

=COMPTASI(B$2:B$75;C3)
=SI(D3=0;C3;"")


DELIMITER //

DROP PROCEDURE IF EXISTS  personaclub //

CREATE PROCEDURE duplicarpersonaclub(var_persona INT) 
BEGIN
	DECLARE final VARCHAR(5) DEFAULT 'START';
	DECLARE ind INT DEFAULT 0;
	DECLARE comptador INT;

	DECLARE var_club VARCHAR(6);
	DECLARE c_clubs CURSOR FOR SELECT p.club FROM m_partes p 
			INNER JOIN m_llicencies l ON p.id = l.parte WHERE l.persona = var_persona;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET final='END';

	OPEN c_clubs;

	FETCH c_clubs INTO var_club;

	IF final <> 'END' THEN	
-- El primer actualitza
		SELECT CONCAT('Club :', var_club);
		UPDATE m_persones SET club = var_club WHERE id = var_persona;
	ELSE 
		SELECT CONCAT('Cap Club');
	END IF;

	WHILE final <> 'END' DO
		FETCH c_clubs INTO var_club;
		IF final <> 'END' THEN	
			INSERT INTO m_persones (`nom`, `cognoms`, `dni`, `datanaixement`, `sexe`, `telefon1`, `telefon2`, `mail`, `addradreca`, `addrpob`, `addrcp`, `addrprovincia`, `addrcomarca`, `addrnacionalitat`, `club`, `dataentrada`, `datamodificacio`, `databaixa`)
			SELECT `nom`, `cognoms`, `dni`, `datanaixement`, `sexe`, `telefon1`, `telefon2`, `mail`, `addradreca`, `addrpob`, `addrcp`, `addrprovincia`, `addrcomarca`, `addrnacionalitat`, var_club, `dataentrada`, `datamodificacio`, `databaixa` 
			FROM m_persones WHERE id = var_persona;
			
		SELECT CONCAT('Club :', var_club);
		END IF;
	END WHILE;
	CLOSE c_clubs;
END //

DELIMITER ;

CALL  duplicarpersonaclub([num persona]);

=CONCATENA("call duplicarpersonaclub(";E3;");")

-- Activar validat
UPDATE m_persones SET validat = 1;


Verificar que coincideixen clubs ACCESS i WEB

SELECT [datos personales].nom, [datos personales].cognoms, [datos personales].DNI, [datos personales].fedeclubDP, m_persones.club, m_persones.id
FROM [datos personales] INNER JOIN m_persones ON [datos personales].DNI = m_persones.dni
WHERE (((m_persones.club)<>fedeClubDP));

!!! HI ha 8 persones !!!


Municipis http://municat.gencat.cat/index.php?page=descarregues
Paisos: http://ca.wikipedia.org/wiki/ISO_3166-1

SELECT municipi, count(*) FROM `municipis_access` group by(municipi) ORDER BY count(*) DESC

Catalunya: 08,25,17,43

SELECT municipi, count( * )
FROM `municipis_access`
WHERE LEFT( cp, 2 )
IN ( 08, 25, 17, 43 )
GROUP BY (municipi )
HAVING count( * ) > 1
ORDER BY count( * ) DESC

SELECT *
FROM municipis_municat m
NATURAL OUTER JOIN municipis_access a ON m.cp = a.cp
WHERE LEFT( a.cp, 2 ) IN ( 08, 25, 17, 43 )
ORDER BY a.cp

SELECT *
FROM municipis_municat m
RIGHT JOIN municipis_access a ON m.cp = a.cp
WHERE LEFT( a.cp, 2 ) IN ( 08, 25, 17, 43 )
ORDER BY a.cp

CREATE TABLE municipis_variscps_cat
SELECT municipi, cp FROM municipis_access 
WHERE municipi IN (
SELECT municipi
FROM `municipis_access`
WHERE LEFT( cp, 2 )
IN ( 08, 25, 17, 43 )
GROUP BY (municipi )
HAVING count( * ) > 1 )
ORDER BY cp

SELECT * FROM `municipis_variscps_cat`  WHERE cp IN 
(SELECT cp
FROM `municipis_variscps_cat` 
GROUP BY cp 
HAVING count(*) > 2)

SELECT * FROM `municipis_variscps_cat`  WHERE CONCAT(municipi,cp) IN 
(SELECT CONCAT(municipi,cp)
FROM `municipis_variscps_cat` 
GROUP BY CONCAT(municipi,cp)
HAVING count(*) = 2)
ORDER BY CONCAT(municipi,cp)


DELIMITER //

DROP PROCEDURE IF EXISTS  cpmunicipis //

CREATE PROCEDURE cpmunicipis() 
BEGIN
	DECLARE final VARCHAR(5) DEFAULT 'START';
	DECLARE var_municipi VARCHAR(50);
	DECLARE var_cp VARCHAR(5);
	DECLARE var_municipiexisteix VARCHAR(50);
	DECLARE var_comarcaexisteix VARCHAR(30);
	DECLARE var_provinciaexisteix VARCHAR(10);
	DECLARE var_cpexisteix VARCHAR(5);
	DECLARE comptador INT;
	DECLARE ind INT DEFAULT 0;
	DECLARE ofile TEXT DEFAULT '';
	DECLARE c_municipis CURSOR FOR SELECT municipi, cp FROM municipis_variscps_cat ORDER BY cp;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET final='END';
	OPEN c_municipis;
	WHILE final <> 'END' DO
		FETCH c_municipis INTO var_municipi, var_cp;
		IF final <> 'END' THEN	
			SELECT count(*) INTO comptador FROM municipis_municat WHERE UCASE(municipi) = var_municipi;
			IF comptador <> 1 THEN
				SELECT CONCAT('ERROR, no existeix municipi:', var_cp ,' - ', var_municipi);
			ELSE
				SELECT municipi, comarca, provincia, cp 
					INTO var_municipiexisteix, var_comarcaexisteix, var_provinciaexisteix, var_cpexisteix 
						FROM municipis_municat WHERE UCASE(municipi) = var_municipi;
				IF var_cpexisteix = var_cp THEN
					SELECT CONCAT('OK, existeix :', var_cp ,' - ', var_municipi);
				ELSE 
					SET ofile = CONCAT(ofile, '\n', 'INSERT INTO municipis_municat VALUES (',
									var_municipiexisteix,
									',',
									var_comarcaexisteix,
									',',
									var_provinciaexisteix,
									',',
									var_cp,
									',',
									var_municipiexisteix,
									');');
				END IF;
			END IF;
		END IF;
		SET ind = ind + 1;	
	END WHILE;
	CLOSE c_municipis;
	SELECT CONCAT('TOTAL :', FORMAT(ind - 1,0));
	SELECT ofile; 
END //

DELIMITER ;

-- Activat tee prova.sql



CREATE TABLE IF NOT EXISTS `m_municipis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `municipi` varchar(50) COLLATE latin1_spanish_ci NOT NULL,
  `comarca` varchar(30) COLLATE latin1_spanish_ci NOT NULL,
  `provincia` varchar(20) COLLATE latin1_spanish_ci NOT NULL,
  `cp` varchar(5) COLLATE latin1_spanish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci AUTO_INCREMENT=1340 ;

--
-- Bolcant dades de la taula `m_municipis`
--

INSERT INTO `m_municipis` (`id`, `municipi`, `comarca`, `provincia`, `cp`) VALUES
(1, 'Montfulla', 'Gironès', 'Girona', '17162'),
(2, 'Abella de la Conca', 'Pallars Jussà', 'Lleida', '25651'),
(3, 'Abrera', 'Baix Llobregat', 'Barcelona', '08630'),
(4, 'Àger', 'Noguera', 'Lleida', '25691'),
(5, 'Agramunt', 'Urgell', 'Lleida', '25310'),
(6, 'Aguilar de Segarra', 'Bages', 'Barcelona', '08256'),
(7, 'Agullana', 'Alt Empordà', 'Girona', '17707'),
(8, 'Aiguafreda', 'Vallès Oriental', 'Barcelona', '08591'),
(9, 'Aiguamúrcia', 'Alt Camp', 'Tarragona', '43815'),
(10, 'Aiguaviva', 'Gironès', 'Girona', '17181'),
(11, 'Aitona', 'Segrià', 'Lleida', '25182'),
(12, 'Els Alamús', 'Segrià', 'Lleida', '25221'),
(13, 'Alàs i Cerc', 'Alt Urgell', 'Lleida', '25718'),
(14, 'L''Albagés', 'Garrigues', 'Lleida', '25155'),
(15, 'Albanyà', 'Alt Empordà', 'Girona', '17733'),
(16, 'Albatàrrec', 'Segrià', 'Lleida', '25171'),
(17, 'Albesa', 'Noguera', 'Lleida', '25135'),
(18, 'L''Albi', 'Garrigues', 'Lleida', '25450'),
(19, 'Albinyana', 'Baix Penedès', 'Tarragona', '43716'),
(20, 'L''Albiol', 'Baix Camp', 'Tarragona', '43479'),
(21, 'Albons', 'Baix Empordà', 'Girona', '17136'),
(22, 'Alcanar', 'Montsià', 'Tarragona', '43530'),
(23, 'Alcanó', 'Segrià', 'Lleida', '25162'),
(24, 'Alcarràs', 'Segrià', 'Lleida', '25180'),
(25, 'Alcoletge', 'Segrià', 'Lleida', '25660'),
(26, 'Alcover', 'Alt Camp', 'Tarragona', '43460'),
(27, 'L''Aldea', 'Baix Ebre', 'Tarragona', '43896'),
(28, 'Aldover', 'Baix Ebre', 'Tarragona', '43591'),
(29, 'L''Aleixar', 'Baix Camp', 'Tarragona', '43381'),
(30, 'Alella', 'Maresme', 'Barcelona', '08328'),
(31, 'Alfara de Carles', 'Baix Ebre', 'Tarragona', '43528'),
(32, 'Alfarràs', 'Segrià', 'Lleida', '25120'),
(33, 'Alfés', 'Segrià', 'Lleida', '25161'),
(34, 'Alforja', 'Baix Camp', 'Tarragona', '43365'),
(35, 'Algerri', 'Noguera', 'Lleida', '25130'),
(36, 'Alguaire', 'Segrià', 'Lleida', '25125'),
(37, 'Alins', 'Pallars Sobirà', 'Lleida', '25574'),
(38, 'Alió', 'Alt Camp', 'Tarragona', '43813'),
(39, 'Almacelles', 'Segrià', 'Lleida', '25100'),
(40, 'Almatret', 'Segrià', 'Lleida', '25187'),
(41, 'Almenar', 'Segrià', 'Lleida', '25126'),
(42, 'Almoster', 'Baix Camp', 'Tarragona', '43393'),
(43, 'Alòs de Balaguer', 'Noguera', 'Lleida', '25737'),
(44, 'Alp', 'Cerdanya', 'Girona', '17538'),
(45, 'Alpens', 'Osona', 'Barcelona', '08587'),
(46, 'Alpicat', 'Segrià', 'Lleida', '25110'),
(47, 'Alt Àneu', 'Pallars Sobirà', 'Lleida', '25587'),
(48, 'Altafulla', 'Tarragonès', 'Tarragona', '43893'),
(49, 'Amer', 'Selva', 'Girona', '17170'),
(50, 'L''Ametlla de Mar', 'Baix Ebre', 'Tarragona', '43860'),
(51, 'L''Ametlla del Vallès', 'Vallès Oriental', 'Barcelona', '08480'),
(52, 'L''Ampolla', 'Baix Ebre', 'Tarragona', '43895'),
(53, 'Amposta', 'Montsià', 'Tarragona', '43870'),
(54, 'Anglès', 'Selva', 'Girona', '17160'),
(55, 'Anglesola', 'Urgell', 'Lleida', '25320'),
(56, 'Arbeca', 'Garrigues', 'Lleida', '25140'),
(57, 'L''Arboç', 'Baix Penedès', 'Tarragona', '43720'),
(58, 'Arbolí', 'Baix Camp', 'Tarragona', '43365'),
(59, 'Arbúcies', 'Selva', 'Girona', '17401'),
(60, 'Arenys de Mar', 'Maresme', 'Barcelona', '08350'),
(61, 'Arenys de Munt', 'Maresme', 'Barcelona', '08358'),
(62, 'Argelaguer', 'Garrotxa', 'Girona', '17853'),
(63, 'Argençola', 'Anoia', 'Barcelona', '08717'),
(64, 'L''Argentera', 'Baix Camp', 'Tarragona', '43773'),
(65, 'Argentona', 'Maresme', 'Barcelona', '08310'),
(66, 'L''Armentera', 'Alt Empordà', 'Girona', '17472'),
(67, 'Arnes', 'Terra Alta', 'Tarragona', '43597'),
(68, 'Arres', 'Val d''Aran', 'Lleida', '25551'),
(69, 'Arsèguel', 'Alt Urgell', 'Lleida', '25722'),
(70, 'Artés', 'Bages', 'Barcelona', '08271'),
(71, 'Artesa de Lleida', 'Segrià', 'Lleida', '25150'),
(72, 'Artesa de Segre', 'Noguera', 'Lleida', '25730'),
(73, 'Ascó', 'Ribera d''Ebre', 'Tarragona', '43791'),
(74, 'Aspa', 'Segrià', 'Lleida', '25151'),
(75, 'Les Avellanes i Santa Linya', 'Noguera', 'Lleida', '25612'),
(76, 'Avià', 'Berguedà', 'Barcelona', '08610'),
(77, 'Avinyó', 'Bages', 'Barcelona', '08279'),
(78, 'Avinyonet de Puigventós', 'Alt Empordà', 'Girona', '17742'),
(79, 'Avinyonet del Penedès', 'Alt Penedès', 'Barcelona', '08793'),
(80, 'Badalona', 'Barcelonès', 'Barcelona', '08911'),
(81, 'Badia del Vallès', 'Vallès Occidental', 'Barcelona', '08214'),
(82, 'Bagà', 'Berguedà', 'Barcelona', '08695'),
(83, 'Baix Pallars', 'Pallars Sobirà', 'Lleida', '25590'),
(84, 'Balaguer', 'Noguera', 'Lleida', '25600'),
(85, 'Balenyà', 'Osona', 'Barcelona', '08550'),
(86, 'Balsareny', 'Bages', 'Barcelona', '08660'),
(87, 'Banyeres del Penedès', 'Baix Penedès', 'Tarragona', '43711'),
(88, 'Banyoles', 'Pla de l''Estany', 'Girona', '17820'),
(89, 'Barbens', 'Pla d''Urgell', 'Lleida', '25262'),
(90, 'Barberà de la Conca', 'Conca de Barberà', 'Tarragona', '43422'),
(91, 'Barberà del Vallès', 'Vallès Occidental', 'Barcelona', '08210'),
(92, 'Barcelona', 'Barcelonès', 'Barcelona', '08002'),
(93, 'La Baronia de Rialb', 'Noguera', 'Lleida', '25747'),
(94, 'Bàscara', 'Alt Empordà', 'Girona', '17483'),
(95, 'Bassella', 'Alt Urgell', 'Lleida', '25289'),
(96, 'Batea', 'Terra Alta', 'Tarragona', '43786'),
(97, 'Bausen', 'Val d''Aran', 'Lleida', '25549'),
(98, 'Begues', 'Baix Llobregat', 'Barcelona', '08859'),
(99, 'Begur', 'Baix Empordà', 'Girona', '17255'),
(100, 'Belianes', 'Urgell', 'Lleida', '25266'),
(101, 'Bellaguarda', 'Garrigues', 'Lleida', '25163'),
(102, 'Bellcaire d''Empordà', 'Baix Empordà', 'Girona', '17141'),
(103, 'Bellcaire d''Urgell', 'Noguera', 'Lleida', '25337'),
(104, 'Bell-lloc d''Urgell', 'Pla d''Urgell', 'Lleida', '25220'),
(105, 'Bellmunt del Priorat', 'Priorat', 'Tarragona', '43738'),
(106, 'Bellmunt d''Urgell', 'Noguera', 'Lleida', '25336'),
(107, 'Bellprat', 'Anoia', 'Barcelona', '43421'),
(108, 'Bellpuig', 'Urgell', 'Lleida', '25250'),
(109, 'Bellvei', 'Baix Penedès', 'Tarragona', '43719'),
(110, 'Bellver de Cerdanya', 'Cerdanya', 'Lleida', '25720'),
(111, 'Bellvís', 'Pla d''Urgell', 'Lleida', '25142'),
(112, 'Benavent de Segrià', 'Segrià', 'Lleida', '25132'),
(113, 'Benifallet', 'Baix Ebre', 'Tarragona', '43512'),
(114, 'Benissanet', 'Ribera d''Ebre', 'Tarragona', '43747'),
(115, 'Berga', 'Berguedà', 'Barcelona', '08600'),
(116, 'Besalú', 'Garrotxa', 'Girona', '17850'),
(117, 'Bescanó', 'Gironès', 'Girona', '17162'),
(118, 'Beuda', 'Garrotxa', 'Girona', '17850'),
(119, 'Bigues i Riells', 'Vallès Oriental', 'Barcelona', '08415'),
(120, 'Biosca', 'Segarra', 'Lleida', '25752'),
(121, 'La Bisbal de Falset', 'Priorat', 'Tarragona', '43372'),
(122, 'La Bisbal del Penedès', 'Baix Penedès', 'Tarragona', '43717'),
(123, 'La Bisbal d''Empordà', 'Baix Empordà', 'Girona', '17100'),
(124, 'Biure', 'Alt Empordà', 'Girona', '17723'),
(125, 'Blancafort', 'Conca de Barberà', 'Tarragona', '43411'),
(126, 'Blanes', 'Selva', 'Girona', '17300'),
(127, 'Boadella i les Escaules', 'Alt Empordà', 'Girona', '17723'),
(128, 'Bolvir', 'Cerdanya', 'Girona', '17539'),
(129, 'Bonastre', 'Baix Penedès', 'Tarragona', '43884'),
(130, 'Es Bòrdes', 'Val d''Aran', 'Lleida', '25551'),
(131, 'Bordils', 'Gironès', 'Girona', '17462'),
(132, 'Les Borges Blanques', 'Garrigues', 'Lleida', '25400'),
(133, 'Les Borges del Camp', 'Baix Camp', 'Tarragona', '43350'),
(134, 'Borrassà', 'Alt Empordà', 'Girona', '17770'),
(135, 'Borredà', 'Berguedà', 'Barcelona', '08619'),
(136, 'Bossòst', 'Val d''Aran', 'Lleida', '25550'),
(137, 'Bot', 'Terra Alta', 'Tarragona', '43785'),
(138, 'Botarell', 'Baix Camp', 'Tarragona', '43772'),
(139, 'Bovera', 'Garrigues', 'Lleida', '25178'),
(140, 'Bràfim', 'Alt Camp', 'Tarragona', '43812'),
(141, 'Breda', 'Selva', 'Girona', '17400'),
(142, 'El Bruc', 'Anoia', 'Barcelona', '08294'),
(143, 'El Brull', 'Osona', 'Barcelona', '08559'),
(144, 'Brunyola', 'Selva', 'Girona', '17441'),
(145, 'Cabacés', 'Priorat', 'Tarragona', '43373'),
(146, 'Cabanabona', 'Noguera', 'Lleida', '25748'),
(147, 'Cabanelles', 'Alt Empordà', 'Girona', '17746'),
(148, 'Cabanes', 'Alt Empordà', 'Girona', '17761'),
(149, 'Les Cabanyes', 'Alt Penedès', 'Barcelona', '08794'),
(150, 'Cabó', 'Alt Urgell', 'Lleida', '25794'),
(151, 'Cabra del Camp', 'Alt Camp', 'Tarragona', '43811'),
(152, 'Cabrera d''Anoia', 'Anoia', 'Barcelona', '08718'),
(153, 'Cabrera de Mar', 'Maresme', 'Barcelona', '08349'),
(154, 'Cabrils', 'Maresme', 'Barcelona', '08348'),
(155, 'Cadaqués', 'Alt Empordà', 'Girona', '17488'),
(156, 'Calaf', 'Anoia', 'Barcelona', '08280'),
(157, 'Calafell', 'Baix Penedès', 'Tarragona', '43820'),
(158, 'Calders', 'Bages', 'Barcelona', '08275'),
(159, 'Caldes de Malavella', 'Selva', 'Girona', '17455'),
(160, 'Caldes de Montbui', 'Vallès Oriental', 'Barcelona', '08140'),
(161, 'Caldes d''Estrac', 'Maresme', 'Barcelona', '08393'),
(162, 'Calella', 'Maresme', 'Barcelona', '08370'),
(163, 'Calldetenes', 'Osona', 'Barcelona', '08519'),
(164, 'Callús', 'Bages', 'Barcelona', '08262'),
(165, 'Sant Antoni de Calonge', 'Baix Empordà', 'Girona', '17251'),
(166, 'Calonge de Segarra', 'Anoia', 'Barcelona', '08281'),
(167, 'Camarasa', 'Noguera', 'Lleida', '25613'),
(168, 'Camarles', 'Baix Ebre', 'Tarragona', '43894'),
(169, 'Cambrils', 'Baix Camp', 'Tarragona', '43850'),
(170, 'Camós', 'Pla de l''Estany', 'Girona', '17834'),
(171, 'Campdevànol', 'Ripollès', 'Girona', '17530'),
(172, 'Campelles', 'Ripollès', 'Girona', '17534'),
(173, 'Campins', 'Vallès Oriental', 'Barcelona', '08472'),
(174, 'Campllong', 'Gironès', 'Girona', '17459'),
(175, 'Camprodon', 'Ripollès', 'Girona', '17867'),
(176, 'Canejan', 'Val d''Aran', 'Lleida', '25548'),
(177, 'Canet d''Adri', 'Gironès', 'Girona', '17199'),
(178, 'Canet de Mar', 'Maresme', 'Barcelona', '08360'),
(179, 'La Canonja', 'Tarragonès', 'Tarragona', '43110'),
(180, 'Canovelles', 'Vallès Oriental', 'Barcelona', '08420'),
(181, 'Cànoves i Samalús', 'Vallès Oriental', 'Barcelona', '08445'),
(182, 'Cantallops', 'Alt Empordà', 'Girona', '17708'),
(183, 'Canyelles', 'Garraf', 'Barcelona', '08811'),
(184, 'Capafonts', 'Baix Camp', 'Tarragona', '43364'),
(185, 'Capçanes', 'Priorat', 'Tarragona', '43776'),
(186, 'Capellades', 'Anoia', 'Barcelona', '08786'),
(187, 'Capmany', 'Alt Empordà', 'Girona', '17750'),
(188, 'Capolat', 'Berguedà', 'Barcelona', '08619'),
(189, 'Cardedeu', 'Vallès Oriental', 'Barcelona', '08440'),
(190, 'Cardona', 'Bages', 'Barcelona', '08261'),
(191, 'Carme', 'Anoia', 'Barcelona', '08787'),
(192, 'Caseres', 'Terra Alta', 'Tarragona', '43787'),
(193, 'Cassà de la Selva', 'Gironès', 'Girona', '17244'),
(194, 'Casserres', 'Berguedà', 'Barcelona', '08693'),
(195, 'Castell de l''Areny', 'Berguedà', 'Barcelona', '08619'),
(196, 'Castell de Mur', 'Pallars Jussà', 'Lleida', '25632'),
(197, 'Castellar de la Ribera', 'Solsonès', 'Lleida', '25289'),
(198, 'Castellar de n''Hug', 'Berguedà', 'Barcelona', '08696'),
(199, 'Castellar del Riu', 'Berguedà', 'Barcelona', '08619'),
(200, 'Castellar del Vallès', 'Vallès Occidental', 'Barcelona', '08211'),
(201, 'Castellbell i el Vilar', 'Bages', 'Barcelona', '08296'),
(202, 'Castellbisbal', 'Vallès Occidental', 'Barcelona', '08755'),
(203, 'Castellcir', 'Vallès Oriental', 'Barcelona', '08183'),
(204, 'Castelldans', 'Garrigues', 'Lleida', '25154'),
(205, 'Castelldefels', 'Baix Llobregat', 'Barcelona', '08860'),
(206, 'Castellet i la Gornal', 'Alt Penedès', 'Barcelona', '08729'),
(207, 'Castellfollit de la Roca', 'Garrotxa', 'Girona', '17856'),
(208, 'Castellfollit de Riubregós', 'Anoia', 'Barcelona', '08281'),
(209, 'Castellfollit del Boix', 'Bages', 'Barcelona', '08255'),
(210, 'Castellgalí', 'Bages', 'Barcelona', '08297'),
(211, 'Castellnou de Bages', 'Bages', 'Barcelona', '08251'),
(212, 'Castellnou de Seana', 'Pla d''Urgell', 'Lleida', '25265'),
(213, 'Castelló de Farfanya', 'Noguera', 'Lleida', '25136'),
(214, 'Castelló d''Empúries', 'Alt Empordà', 'Girona', '17486'),
(215, 'Castellolí', 'Anoia', 'Barcelona', '08719'),
(216, 'Castell-Platja d''Aro', 'Baix Empordà', 'Girona', '17250'),
(217, 'Castellserà', 'Urgell', 'Lleida', '25334'),
(218, 'Castellterçol', 'Vallès Oriental', 'Barcelona', '08183'),
(219, 'Castellvell del Camp', 'Baix Camp', 'Tarragona', '43392'),
(220, 'Castellví de la Marca', 'Alt Penedès', 'Barcelona', '08732'),
(221, 'Castellví de Rosanes', 'Baix Llobregat', 'Barcelona', '08769'),
(222, 'El Catllar', 'Tarragonès', 'Tarragona', '43764'),
(223, 'Cava', 'Alt Urgell', 'Lleida', '25722'),
(224, 'La Cellera de Ter', 'Selva', 'Girona', '17165'),
(225, 'Celrà', 'Gironès', 'Girona', '17460'),
(226, 'Centelles', 'Osona', 'Barcelona', '08540'),
(227, 'Cercs', 'Berguedà', 'Barcelona', '08698'),
(228, 'Cerdanyola del Vallès', 'Vallès Occidental', 'Barcelona', '08290'),
(229, 'Cervelló', 'Baix Llobregat', 'Barcelona', '08758'),
(230, 'Cervera', 'Segarra', 'Lleida', '25200'),
(231, 'Cervià de les Garrigues', 'Garrigues', 'Lleida', '25460'),
(232, 'Cervià de Ter', 'Gironès', 'Girona', '17464'),
(233, 'Cistella', 'Alt Empordà', 'Girona', '17741'),
(234, 'Ciutadilla', 'Urgell', 'Lleida', '25341'),
(235, 'Clariana de Cardener', 'Solsonès', 'Lleida', '25290'),
(236, 'El Cogul', 'Garrigues', 'Lleida', '25152'),
(237, 'Colera', 'Alt Empordà', 'Girona', '17496'),
(238, 'Coll de Nargó', 'Alt Urgell', 'Lleida', '25793'),
(239, 'Collbató', 'Baix Llobregat', 'Barcelona', '08293'),
(240, 'Colldejou', 'Baix Camp', 'Tarragona', '43310'),
(241, 'Collsuspina', 'Osona', 'Barcelona', '08178'),
(242, 'Colomers', 'Baix Empordà', 'Girona', '17144'),
(243, 'La Coma i la Pedra', 'Solsonès', 'Lleida', '25284'),
(244, 'Conca de Dalt', 'Pallars Jussà', 'Lleida', '25500'),
(245, 'Conesa', 'Conca de Barberà', 'Tarragona', '43427'),
(246, 'Constantí', 'Tarragonès', 'Tarragona', '43120'),
(247, 'Copons', 'Anoia', 'Barcelona', '08281'),
(248, 'Corbera de Llobregat', 'Baix Llobregat', 'Barcelona', '08757'),
(249, 'Corbera d''Ebre', 'Terra Alta', 'Tarragona', '43784'),
(250, 'Corbins', 'Segrià', 'Lleida', '25137'),
(251, 'Corçà', 'Baix Empordà', 'Girona', '17121'),
(252, 'Cornellà de Llobregat', 'Baix Llobregat', 'Barcelona', '08940'),
(253, 'Cornellà del Terri', 'Pla de l''Estany', 'Girona', '17844'),
(254, 'Cornudella de Montsant', 'Priorat', 'Tarragona', '43360'),
(255, 'Creixell', 'Tarragonès', 'Tarragona', '43839'),
(256, 'Crespià', 'Pla de l''Estany', 'Girona', '17832'),
(257, 'Cruïlles, Monells i Sant Sadurní de l''Heura', 'Baix Empordà', 'Girona', '17118'),
(258, 'Cubelles', 'Garraf', 'Barcelona', '08880'),
(259, 'Cubells', 'Noguera', 'Lleida', '25737'),
(260, 'Cunit', 'Baix Penedès', 'Tarragona', '43881'),
(261, 'Darnius', 'Alt Empordà', 'Girona', '17722'),
(262, 'Das', 'Cerdanya', 'Girona', '17538'),
(263, 'Deltebre', 'Baix Ebre', 'Tarragona', '43580'),
(264, 'Dosrius', 'Maresme', 'Barcelona', '08319'),
(265, 'Duesaigües', 'Baix Camp', 'Tarragona', '43773'),
(266, 'L''Escala', 'Alt Empordà', 'Girona', '17130'),
(267, 'Esparreguera', 'Baix Llobregat', 'Barcelona', '08292'),
(268, 'Espinelves', 'Osona', 'Girona', '17405'),
(269, 'L''Espluga Calba', 'Garrigues', 'Lleida', '25410'),
(270, 'L''Espluga de Francolí', 'Conca de Barberà', 'Tarragona', '43440'),
(271, 'Esplugues de Llobregat', 'Baix Llobregat', 'Barcelona', '08950'),
(272, 'Espolla', 'Alt Empordà', 'Girona', '17753'),
(273, 'Esponellà', 'Pla de l''Estany', 'Girona', '17832'),
(274, 'Espot', 'Pallars Sobirà', 'Lleida', '25597'),
(275, 'L''Espunyola', 'Berguedà', 'Barcelona', '08614'),
(276, 'Estamariu', 'Alt Urgell', 'Lleida', '25719'),
(277, 'L''Estany', 'Bages', 'Barcelona', '08148'),
(278, 'Estaràs', 'Segarra', 'Lleida', '25214'),
(279, 'Esterri d''Àneu', 'Pallars Sobirà', 'Lleida', '25580'),
(280, 'Esterri de Cardós', 'Pallars Sobirà', 'Lleida', '25571'),
(281, 'Falset', 'Priorat', 'Tarragona', '43730'),
(282, 'El Far d''Empordà', 'Alt Empordà', 'Girona', '17469'),
(283, 'Farrera', 'Pallars Sobirà', 'Lleida', '25595'),
(284, 'La Fatarella', 'Terra Alta', 'Tarragona', '43781'),
(285, 'La Febró', 'Baix Camp', 'Tarragona', '43364'),
(286, 'Figaró-Montmany', 'Vallès Oriental', 'Barcelona', '08590'),
(287, 'Fígols', 'Berguedà', 'Barcelona', '08698'),
(288, 'Fígols i Alinyà', 'Alt Urgell', 'Lleida', '25794'),
(289, 'La Figuera', 'Priorat', 'Tarragona', '43736'),
(290, 'Figueres', 'Alt Empordà', 'Girona', '17600'),
(291, 'Figuerola del Camp', 'Alt Camp', 'Tarragona', '43811'),
(292, 'Flaçà', 'Gironès', 'Girona', '17463'),
(293, 'Flix', 'Ribera d''Ebre', 'Tarragona', '43750'),
(294, 'La Floresta', 'Garrigues', 'Lleida', '25413'),
(295, 'Fogars de la Selva', 'Selva', 'Barcelona', '08495'),
(296, 'Fogars de Montclús', 'Vallès Oriental', 'Barcelona', '08470'),
(297, 'Foixà', 'Baix Empordà', 'Girona', '17132'),
(298, 'Folgueroles', 'Osona', 'Barcelona', '08519'),
(299, 'Fondarella', 'Pla d''Urgell', 'Lleida', '25244'),
(300, 'Fonollosa', 'Bages', 'Barcelona', '08259'),
(301, 'Fontanals de Cerdanya', 'Cerdanya', 'Girona', '17538'),
(302, 'Fontanilles', 'Baix Empordà', 'Girona', '17257'),
(303, 'Fontcoberta', 'Pla de l''Estany', 'Girona', '17833'),
(304, 'Font-rubí', 'Alt Penedès', 'Barcelona', '08736'),
(305, 'Foradada', 'Noguera', 'Lleida', '25737'),
(306, 'Forallac', 'Baix Empordà', 'Girona', '17111'),
(307, 'Forès', 'Conca de Barberà', 'Tarragona', '43425'),
(308, 'Fornells de la Selva', 'Gironès', 'Girona', '17458'),
(309, 'Fortià', 'Alt Empordà', 'Girona', '17469'),
(310, 'Les Franqueses del Vallès', 'Vallès Oriental', 'Barcelona', '08520'),
(311, 'Freginals', 'Montsià', 'Tarragona', '43558'),
(312, 'La Fuliola', 'Urgell', 'Lleida', '25332'),
(313, 'Fulleda', 'Garrigues', 'Lleida', '25411'),
(314, 'Gaià', 'Bages', 'Barcelona', '08672'),
(315, 'La Galera', 'Montsià', 'Tarragona', '43515'),
(316, 'Gallifa', 'Vallès Occidental', 'Barcelona', '08146'),
(317, 'Gandesa', 'Terra Alta', 'Tarragona', '43780'),
(318, 'Garcia', 'Ribera d''Ebre', 'Tarragona', '43749'),
(319, 'Els Garidells', 'Alt Camp', 'Tarragona', '43153'),
(320, 'La Garriga', 'Vallès Oriental', 'Barcelona', '08530'),
(321, 'Garrigàs', 'Alt Empordà', 'Girona', '17476'),
(322, 'Garrigoles', 'Baix Empordà', 'Girona', '17466'),
(323, 'Garriguella', 'Alt Empordà', 'Girona', '17780'),
(324, 'Gavà', 'Baix Llobregat', 'Barcelona', '08850'),
(325, 'Gavet de la Conca', 'Pallars Jussà', 'Lleida', '25639'),
(326, 'Gelida', 'Alt Penedès', 'Barcelona', '08790'),
(327, 'Ger', 'Cerdanya', 'Girona', '17539'),
(328, 'Gimenells i el Pla de la Font', 'Segrià', 'Lleida', '25112'),
(329, 'Ginestar', 'Ribera d''Ebre', 'Tarragona', '43748'),
(330, 'Girona', 'Gironès', 'Girona', '17004'),
(331, 'Gironella', 'Berguedà', 'Barcelona', '08680'),
(332, 'Gisclareny', 'Berguedà', 'Barcelona', '08695'),
(333, 'Godall', 'Montsià', 'Tarragona', '43516'),
(334, 'Golmés', 'Pla d''Urgell', 'Lleida', '25241'),
(335, 'Gombrèn', 'Ripollès', 'Girona', '17531'),
(336, 'Gósol', 'Berguedà', 'Lleida', '25716'),
(337, 'La Granada', 'Alt Penedès', 'Barcelona', '08792'),
(338, 'La Granadella', 'Garrigues', 'Lleida', '25177'),
(339, 'Granera', 'Vallès Oriental', 'Barcelona', '08183'),
(340, 'La Granja d''Escarp', 'Segrià', 'Lleida', '25185'),
(341, 'Granollers', 'Vallès Oriental', 'Barcelona', '08401'),
(342, 'Granyanella', 'Segarra', 'Lleida', '25218'),
(343, 'Granyena de les Garrigues', 'Garrigues', 'Lleida', '25160'),
(344, 'Granyena de Segarra', 'Segarra', 'Lleida', '25217'),
(345, 'Gratallops', 'Priorat', 'Tarragona', '43737'),
(346, 'Gualba', 'Vallès Oriental', 'Barcelona', '08474'),
(347, 'Gualta', 'Baix Empordà', 'Girona', '17257'),
(348, 'Guardiola de Berguedà', 'Berguedà', 'Barcelona', '08694'),
(349, 'Els Guiamets', 'Priorat', 'Tarragona', '43777'),
(350, 'Guils de Cerdanya', 'Cerdanya', 'Girona', '17528'),
(351, 'Guimerà', 'Urgell', 'Lleida', '25341'),
(352, 'La Guingueta d''Àneu', 'Pallars Sobirà', 'Lleida', '25597'),
(353, 'Guissona', 'Segarra', 'Lleida', '25210'),
(354, 'Guixers', 'Solsonès', 'Lleida', '25285'),
(355, 'Gurb', 'Osona', 'Barcelona', '08503'),
(356, 'Horta de Sant Joan', 'Terra Alta', 'Tarragona', '43596'),
(357, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08901'),
(358, 'Els Hostalets de Pierola', 'Anoia', 'Barcelona', '08781'),
(359, 'Hostalric', 'Selva', 'Girona', '17450'),
(360, 'Igualada', 'Anoia', 'Barcelona', '08700'),
(361, 'Isona i Conca Dellà', 'Pallars Jussà', 'Lleida', '25650'),
(362, 'Isòvol', 'Cerdanya', 'Girona', '17539'),
(363, 'Ivars de Noguera', 'Noguera', 'Lleida', '25122'),
(364, 'Ivars d''Urgell', 'Pla d''Urgell', 'Lleida', '25260'),
(365, 'Ivorra', 'Segarra', 'Lleida', '25216'),
(366, 'Jafre', 'Baix Empordà', 'Girona', '17143'),
(367, 'La Jonquera', 'Alt Empordà', 'Girona', '17700'),
(368, 'Jorba', 'Anoia', 'Barcelona', '08719'),
(369, 'Josa i Tuixén', 'Alt Urgell', 'Lleida', '25717'),
(370, 'Juià', 'Gironès', 'Girona', '17462'),
(371, 'Juncosa', 'Garrigues', 'Lleida', '25165'),
(372, 'Juneda', 'Garrigues', 'Lleida', '25430'),
(373, 'Les', 'Val d''Aran', 'Lleida', '25540'),
(374, 'Linyola', 'Pla d''Urgell', 'Lleida', '25240'),
(375, 'La Llacuna', 'Anoia', 'Barcelona', '08779'),
(376, 'Lladó', 'Alt Empordà', 'Girona', '17745'),
(377, 'Lladorre', 'Pallars Sobirà', 'Lleida', '25576'),
(378, 'Lladurs', 'Solsonès', 'Lleida', '25283'),
(379, 'La Llagosta', 'Vallès Oriental', 'Barcelona', '08120'),
(380, 'Llagostera', 'Gironès', 'Girona', '17240'),
(381, 'Llambilles', 'Gironès', 'Girona', '17243'),
(382, 'Llanars', 'Ripollès', 'Girona', '17869'),
(383, 'Llançà', 'Alt Empordà', 'Girona', '17490'),
(384, 'Llardecans', 'Segrià', 'Lleida', '25186'),
(385, 'Llavorsí', 'Pallars Sobirà', 'Lleida', '25595'),
(386, 'Lleida', 'Segrià', 'Lleida', '25007'),
(387, 'Llers', 'Alt Empordà', 'Girona', '17730'),
(388, 'Lles de Cerdanya', 'Cerdanya', 'Lleida', '25726'),
(389, 'Lliçà d''Amunt', 'Vallès Oriental', 'Barcelona', '08186'),
(390, 'Lliçà de Vall', 'Vallès Oriental', 'Barcelona', '08185'),
(391, 'Llimiana', 'Pallars Jussà', 'Lleida', '25639'),
(392, 'Llinars del Vallès', 'Vallès Oriental', 'Barcelona', '08450'),
(393, 'Llívia', 'Cerdanya', 'Girona', '17527'),
(394, 'El Lloar', 'Priorat', 'Tarragona', '43737'),
(395, 'Llobera', 'Solsonès', 'Lleida', '25281'),
(396, 'Llorac', 'Conca de Barberà', 'Tarragona', '43427'),
(397, 'Llorenç del Penedès', 'Baix Penedès', 'Tarragona', '43712'),
(398, 'Lloret de Mar', 'Selva', 'Girona', '17310'),
(399, 'Les Llosses', 'Ripollès', 'Girona', '17512'),
(400, 'Lluçà', 'Osona', 'Barcelona', '08514'),
(401, 'Maçanet de Cabrenys', 'Alt Empordà', 'Girona', '17720'),
(402, 'Maçanet de la Selva', 'Selva', 'Girona', '17412'),
(403, 'Madremanya', 'Gironès', 'Girona', '17462'),
(404, 'Maià de Montcal', 'Garrotxa', 'Girona', '17851'),
(405, 'Maials', 'Segrià', 'Lleida', '25179'),
(406, 'Maldà', 'Urgell', 'Lleida', '25266'),
(407, 'Malgrat de Mar', 'Maresme', 'Barcelona', '08380'),
(408, 'Malla', 'Osona', 'Barcelona', '08519'),
(409, 'Manlleu', 'Osona', 'Barcelona', '08560'),
(410, 'Manresa', 'Bages', 'Barcelona', '08241'),
(411, 'Marçà', 'Priorat', 'Tarragona', '43775'),
(412, 'Margalef', 'Priorat', 'Tarragona', '43371'),
(413, 'Marganell', 'Bages', 'Barcelona', '08298'),
(414, 'Martorell', 'Baix Llobregat', 'Barcelona', '08760'),
(415, 'Martorelles', 'Vallès Oriental', 'Barcelona', '08107'),
(416, 'Mas de Barberans', 'Montsià', 'Tarragona', '43514'),
(417, 'Masarac', 'Alt Empordà', 'Girona', '17763'),
(418, 'Masdenverge', 'Montsià', 'Tarragona', '43878'),
(419, 'Les Masies de Roda', 'Osona', 'Barcelona', '08510'),
(420, 'Les Masies de Voltregà', 'Osona', 'Barcelona', '08509'),
(421, 'Masllorenç', 'Baix Penedès', 'Tarragona', '43718'),
(422, 'El Masnou', 'Maresme', 'Barcelona', '08320'),
(423, 'La Masó', 'Alt Camp', 'Tarragona', '43143'),
(424, 'Maspujols', 'Baix Camp', 'Tarragona', '43382'),
(425, 'Masquefa', 'Anoia', 'Barcelona', '08783'),
(426, 'El Masroig', 'Priorat', 'Tarragona', '43736'),
(427, 'Massalcoreig', 'Segrià', 'Lleida', '25184'),
(428, 'Massanes', 'Selva', 'Girona', '17452'),
(429, 'Massoteres', 'Segarra', 'Lleida', '25211'),
(430, 'Matadepera', 'Vallès Occidental', 'Barcelona', '08230'),
(431, 'Mataró', 'Maresme', 'Barcelona', '08301'),
(432, 'Mediona', 'Alt Penedès', 'Barcelona', '08773'),
(433, 'Menàrguens', 'Noguera', 'Lleida', '25139'),
(434, 'Meranges', 'Cerdanya', 'Girona', '17539'),
(435, 'Mieres', 'Garrotxa', 'Girona', '17830'),
(436, 'El Milà', 'Alt Camp', 'Tarragona', '43143'),
(437, 'Miralcamp', 'Pla d''Urgell', 'Lleida', '25242'),
(438, 'Miravet', 'Ribera d''Ebre', 'Tarragona', '43747'),
(439, 'Moià', 'Bages', 'Barcelona', '08180'),
(440, 'El Molar', 'Priorat', 'Tarragona', '43736'),
(441, 'Molins de Rei', 'Baix Llobregat', 'Barcelona', '08750'),
(442, 'Mollerussa', 'Pla d''Urgell', 'Lleida', '25230'),
(443, 'Mollet de Peralada', 'Alt Empordà', 'Girona', '17752'),
(444, 'Mollet del Vallès', 'Vallès Oriental', 'Barcelona', '08100'),
(445, 'Molló', 'Ripollès', 'Girona', '17868'),
(446, 'La Molsosa', 'Solsonès', 'Lleida', '08281'),
(447, 'Monistrol de Calders', 'Bages', 'Barcelona', '08275'),
(448, 'Monistrol de Montserrat', 'Bages', 'Barcelona', '08691'),
(449, 'Montagut i Oix', 'Garrotxa', 'Girona', '17855'),
(450, 'Montblanc', 'Conca de Barberà', 'Tarragona', '43400'),
(451, 'Montbrió del Camp', 'Baix Camp', 'Tarragona', '43340'),
(452, 'Montcada i Reixac', 'Vallès Occidental', 'Barcelona', '08110'),
(453, 'Montclar', 'Berguedà', 'Barcelona', '08619'),
(454, 'Montellà i Martinet', 'Cerdanya', 'Lleida', '25725'),
(455, 'Montesquiu', 'Osona', 'Barcelona', '08585'),
(456, 'Montferrer i Castellbò', 'Alt Urgell', 'Lleida', '25711'),
(457, 'Montferri', 'Alt Camp', 'Tarragona', '43812'),
(458, 'Montgai', 'Noguera', 'Lleida', '25616'),
(459, 'Montgat', 'Maresme', 'Barcelona', '08390'),
(460, 'Montmajor', 'Berguedà', 'Barcelona', '08612'),
(461, 'Montmaneu', 'Anoia', 'Barcelona', '08717'),
(462, 'El Montmell', 'Baix Penedès', 'Tarragona', '43718'),
(463, 'Montmeló', 'Vallès Oriental', 'Barcelona', '08160'),
(464, 'Montoliu de Lleida', 'Segrià', 'Lleida', '25172'),
(465, 'Montoliu de Segarra', 'Segarra', 'Lleida', '25217'),
(466, 'Montornès de Segarra', 'Segarra', 'Lleida', '25340'),
(467, 'Montornès del Vallès', 'Vallès Oriental', 'Barcelona', '08170'),
(468, 'Mont-ral', 'Alt Camp', 'Tarragona', '43364'),
(469, 'Mont-ras', 'Baix Empordà', 'Girona', '17253'),
(470, 'Mont-roig del Camp', 'Baix Camp', 'Tarragona', '43300'),
(471, 'Montseny', 'Vallès Oriental', 'Barcelona', '08460'),
(472, 'Móra d''Ebre', 'Ribera d''Ebre', 'Tarragona', '43740'),
(473, 'Móra la Nova', 'Ribera d''Ebre', 'Tarragona', '43770'),
(474, 'El Morell', 'Tarragonès', 'Tarragona', '43760'),
(475, 'La Morera de Montsant', 'Priorat', 'Tarragona', '43361'),
(476, 'Muntanyola', 'Osona', 'Barcelona', '08505'),
(477, 'Mura', 'Bages', 'Barcelona', '08279'),
(478, 'Nalec', 'Urgell', 'Lleida', '25341'),
(479, 'Naut Aran', 'Val d''Aran', 'Lleida', '25598'),
(480, 'Navarcles', 'Bages', 'Barcelona', '08270'),
(481, 'Navàs', 'Bages', 'Barcelona', '08670'),
(482, 'Navata', 'Alt Empordà', 'Girona', '17744'),
(483, 'Navès', 'Solsonès', 'Lleida', '25286'),
(484, 'La Nou de Berguedà', 'Berguedà', 'Barcelona', '08698'),
(485, 'La Nou de Gaià', 'Tarragonès', 'Tarragona', '43763'),
(486, 'Nulles', 'Alt Camp', 'Tarragona', '43887'),
(487, 'Odèn', 'Solsonès', 'Lleida', '25283'),
(488, 'Òdena', 'Anoia', 'Barcelona', '08711'),
(489, 'Ogassa', 'Ripollès', 'Girona', '17861'),
(490, 'Olèrdola', 'Alt Penedès', 'Barcelona', '08734'),
(491, 'Olesa de Bonesvalls', 'Alt Penedès', 'Barcelona', '08795'),
(492, 'Olesa de Montserrat', 'Baix Llobregat', 'Barcelona', '08640'),
(493, 'Oliana', 'Alt Urgell', 'Lleida', '25790'),
(494, 'Oliola', 'Noguera', 'Lleida', '25749'),
(495, 'Olius', 'Solsonès', 'Lleida', '25280'),
(496, 'Olivella', 'Garraf', 'Barcelona', '08810'),
(497, 'Olost', 'Osona', 'Barcelona', '08516'),
(498, 'Olot', 'Garrotxa', 'Girona', '17800'),
(499, 'Les Oluges', 'Segarra', 'Lleida', '25214'),
(500, 'Olvan', 'Berguedà', 'Barcelona', '08611'),
(501, 'Els Omellons', 'Garrigues', 'Lleida', '25412'),
(502, 'Els Omells de na Gaia', 'Urgell', 'Lleida', '25268'),
(503, 'Ordis', 'Alt Empordà', 'Girona', '17772'),
(504, 'Organyà', 'Alt Urgell', 'Lleida', '25794'),
(505, 'Orís', 'Osona', 'Barcelona', '08573'),
(506, 'Oristà', 'Osona', 'Barcelona', '08518'),
(507, 'Orpí', 'Anoia', 'Barcelona', '08787'),
(508, 'Òrrius', 'Maresme', 'Barcelona', '08317'),
(509, 'Os de Balaguer', 'Noguera', 'Lleida', '25610'),
(510, 'Osor', 'Selva', 'Girona', '17161'),
(511, 'Ossó de Sió', 'Urgell', 'Lleida', '25318'),
(512, 'Pacs del Penedès', 'Alt Penedès', 'Barcelona', '08796'),
(513, 'Palafolls', 'Maresme', 'Barcelona', '08389'),
(514, 'Palafrugell', 'Baix Empordà', 'Girona', '17200'),
(515, 'Palamós', 'Baix Empordà', 'Girona', '17230'),
(516, 'El Palau d''Anglesola', 'Pla d''Urgell', 'Lleida', '25243'),
(517, 'Palau de Santa Eulàlia', 'Alt Empordà', 'Girona', '17476'),
(518, 'Palau-sator', 'Baix Empordà', 'Girona', '17256'),
(519, 'Palau-saverdera', 'Alt Empordà', 'Girona', '17495'),
(520, 'Palau-solità i Plegamans', 'Vallès Occidental', 'Barcelona', '08184'),
(521, 'Els Pallaresos', 'Tarragonès', 'Tarragona', '43151'),
(522, 'Pallejà', 'Baix Llobregat', 'Barcelona', '08780'),
(523, 'La Palma de Cervelló', 'Baix Llobregat', 'Barcelona', '08756'),
(524, 'La Palma d''Ebre', 'Ribera d''Ebre', 'Tarragona', '43370'),
(525, 'Palol de Revardit', 'Pla de l''Estany', 'Girona', '17843'),
(526, 'Pals', 'Baix Empordà', 'Girona', '17256'),
(527, 'El Papiol', 'Baix Llobregat', 'Barcelona', '08754'),
(528, 'Pardines', 'Ripollès', 'Girona', '17534'),
(529, 'Parets del Vallès', 'Vallès Oriental', 'Barcelona', '08150'),
(530, 'Parlavà', 'Baix Empordà', 'Girona', '17133'),
(531, 'Passanant i Belltall', 'Conca de Barberà', 'Tarragona', '43425'),
(532, 'Pau', 'Alt Empordà', 'Girona', '17494'),
(533, 'Paüls', 'Baix Ebre', 'Tarragona', '43593'),
(534, 'Pedret i Marzà', 'Alt Empordà', 'Girona', '17493'),
(535, 'Penelles', 'Noguera', 'Lleida', '25335'),
(536, 'La Pera', 'Baix Empordà', 'Girona', '17120'),
(537, 'Perafita', 'Osona', 'Barcelona', '08589'),
(538, 'Perafort', 'Tarragonès', 'Tarragona', '43152'),
(539, 'Peralada', 'Alt Empordà', 'Girona', '17491'),
(540, 'Peramola', 'Alt Urgell', 'Lleida', '25790'),
(541, 'El Perelló', 'Baix Ebre', 'Tarragona', '43519'),
(542, 'Piera', 'Anoia', 'Barcelona', '08784'),
(543, 'Les Piles', 'Conca de Barberà', 'Tarragona', '43428'),
(544, 'Pineda de Mar', 'Maresme', 'Barcelona', '08397'),
(545, 'El Pinell de Brai', 'Terra Alta', 'Tarragona', '43594'),
(546, 'Pinell de Solsonès', 'Solsonès', 'Lleida', '25286'),
(547, 'Pinós', 'Solsonès', 'Lleida', '25287'),
(548, 'Pira', 'Conca de Barberà', 'Tarragona', '43423'),
(549, 'El Pla de Santa Maria', 'Alt Camp', 'Tarragona', '43810'),
(550, 'El Pla del Penedès', 'Alt Penedès', 'Barcelona', '08733'),
(551, 'Les Planes d''Hostoles', 'Garrotxa', 'Girona', '17172'),
(552, 'Planoles', 'Ripollès', 'Girona', '17535'),
(553, 'Els Plans de Sió', 'Segarra', 'Lleida', '25212'),
(554, 'El Poal', 'Pla d''Urgell', 'Lleida', '25143'),
(555, 'La Pobla de Cérvoles', 'Garrigues', 'Lleida', '25471'),
(556, 'La Pobla de Claramunt', 'Anoia', 'Barcelona', '08787'),
(557, 'La Pobla de Lillet', 'Berguedà', 'Barcelona', '08696'),
(558, 'La Pobla de Mafumet', 'Tarragonès', 'Tarragona', '43140'),
(559, 'La Pobla de Massaluca', 'Terra Alta', 'Tarragona', '43783'),
(560, 'La Pobla de Montornès', 'Tarragonès', 'Tarragona', '43761'),
(561, 'La Pobla de Segur', 'Pallars Jussà', 'Lleida', '25500'),
(562, 'Poboleda', 'Priorat', 'Tarragona', '43376'),
(563, 'Polinyà', 'Vallès Occidental', 'Barcelona', '08213'),
(564, 'El Pont d''Armentera', 'Alt Camp', 'Tarragona', '43817'),
(565, 'El Pont de Bar', 'Alt Urgell', 'Lleida', '25723'),
(566, 'Pont de Molins', 'Alt Empordà', 'Girona', '17706'),
(567, 'El Pont de Suert', 'Alta Ribagorça', 'Lleida', '25520'),
(568, 'El Pont de Vilomara i Rocafort', 'Bages', 'Barcelona', '08254'),
(569, 'Pontils', 'Conca de Barberà', 'Tarragona', '43421'),
(570, 'Pontons', 'Alt Penedès', 'Barcelona', '08738'),
(571, 'Pontós', 'Alt Empordà', 'Girona', '17773'),
(572, 'Ponts', 'Noguera', 'Lleida', '25740'),
(573, 'Porqueres', 'Pla de l''Estany', 'Girona', '17834'),
(574, 'Porrera', 'Priorat', 'Tarragona', '43739'),
(575, 'El Port de la Selva', 'Alt Empordà', 'Girona', '17489'),
(576, 'Portbou', 'Alt Empordà', 'Girona', '17497'),
(577, 'La Portella', 'Segrià', 'Lleida', '25134'),
(578, 'Pradell de la Teixeta', 'Priorat', 'Tarragona', '43774'),
(579, 'Prades', 'Baix Camp', 'Tarragona', '43364'),
(580, 'Prat de Comte', 'Terra Alta', 'Tarragona', '43595'),
(581, 'El Prat de Llobregat', 'Baix Llobregat', 'Barcelona', '08820'),
(582, 'Pratdip', 'Baix Camp', 'Tarragona', '43320'),
(583, 'Prats de Lluçanès', 'Osona', 'Barcelona', '08513'),
(584, 'Els Prats de Rei', 'Anoia', 'Barcelona', '08281'),
(585, 'Prats i Sansor', 'Cerdanya', 'Lleida', '25721'),
(586, 'Preixana', 'Urgell', 'Lleida', '25263'),
(587, 'Preixens', 'Noguera', 'Lleida', '25316'),
(588, 'Premià de Dalt', 'Maresme', 'Barcelona', '08338'),
(589, 'Premià de Mar', 'Maresme', 'Barcelona', '08330'),
(590, 'Les Preses', 'Garrotxa', 'Girona', '17178'),
(591, 'Prullans', 'Cerdanya', 'Lleida', '25727'),
(592, 'Puigcerdà', 'Cerdanya', 'Girona', '17520'),
(593, 'Puigdàlber', 'Alt Penedès', 'Barcelona', '08797'),
(594, 'Puiggròs', 'Garrigues', 'Lleida', '25420'),
(595, 'Puigpelat', 'Alt Camp', 'Tarragona', '43812'),
(596, 'Puig-reig', 'Berguedà', 'Barcelona', '08692'),
(597, 'Puigverd d''Agramunt', 'Urgell', 'Lleida', '25318'),
(598, 'Puigverd de Lleida', 'Segrià', 'Lleida', '25153'),
(599, 'Pujalt', 'Anoia', 'Barcelona', '08282'),
(600, 'La Quar', 'Berguedà', 'Barcelona', '08619'),
(601, 'Quart', 'Gironès', 'Girona', '17242'),
(602, 'Queralbs', 'Ripollès', 'Girona', '17534'),
(603, 'Querol', 'Alt Camp', 'Tarragona', '43816'),
(604, 'Rabós', 'Alt Empordà', 'Girona', '17754'),
(605, 'Rajadell', 'Bages', 'Barcelona', '08289'),
(606, 'Rasquera', 'Ribera d''Ebre', 'Tarragona', '43513'),
(607, 'Regencós', 'Baix Empordà', 'Girona', '17214'),
(608, 'Rellinars', 'Vallès Occidental', 'Barcelona', '08299'),
(609, 'Renau', 'Tarragonès', 'Tarragona', '43886'),
(610, 'Reus', 'Baix Camp', 'Tarragona', '43201'),
(611, 'Rialp', 'Pallars Sobirà', 'Lleida', '25594'),
(612, 'La Riba', 'Alt Camp', 'Tarragona', '43450'),
(613, 'Riba-roja d''Ebre', 'Ribera d''Ebre', 'Tarragona', '43790'),
(614, 'Ribera d''Ondara', 'Segarra', 'Lleida', '25213'),
(615, 'Ribera d''Urgellet', 'Alt Urgell', 'Lleida', '25796'),
(616, 'Ribes de Freser', 'Ripollès', 'Girona', '17534'),
(617, 'Riells i Viabrea', 'Selva', 'Girona', '17404'),
(618, 'La Riera de Gaià', 'Tarragonès', 'Tarragona', '43762'),
(619, 'Riner', 'Solsonès', 'Lleida', '25290'),
(620, 'Ripoll', 'Ripollès', 'Girona', '17500'),
(621, 'Ripollet', 'Vallès Occidental', 'Barcelona', '08291'),
(622, 'Riu de Cerdanya', 'Cerdanya', 'Lleida', '25721'),
(623, 'Riudarenes', 'Selva', 'Girona', '17421'),
(624, 'Riudaura', 'Garrotxa', 'Girona', '17179'),
(625, 'Riudecanyes', 'Baix Camp', 'Tarragona', '43771'),
(626, 'Riudecols', 'Baix Camp', 'Tarragona', '43390'),
(627, 'Riudellots de la Selva', 'Selva', 'Girona', '17457'),
(628, 'Riudoms', 'Baix Camp', 'Tarragona', '43330'),
(629, 'Riumors', 'Alt Empordà', 'Girona', '17469'),
(630, 'La Roca del Vallès', 'Vallès Oriental', 'Barcelona', '08430'),
(631, 'Rocafort de Queralt', 'Conca de Barberà', 'Tarragona', '43426'),
(632, 'Roda de Barà', 'Tarragonès', 'Tarragona', '43883'),
(633, 'Roda de Ter', 'Osona', 'Barcelona', '08510'),
(634, 'Rodonyà', 'Alt Camp', 'Tarragona', '43812'),
(635, 'Roquetes', 'Baix Ebre', 'Tarragona', '43520'),
(636, 'Roses', 'Alt Empordà', 'Girona', '17480'),
(637, 'Rosselló', 'Segrià', 'Lleida', '25124'),
(638, 'El Rourell', 'Alt Camp', 'Tarragona', '43142'),
(639, 'Rubí', 'Vallès Occidental', 'Barcelona', '08191'),
(640, 'Rubió', 'Anoia', 'Barcelona', '08719'),
(641, 'Rupià', 'Baix Empordà', 'Girona', '17131'),
(642, 'Rupit i Pruit', 'Osona', 'Barcelona', '08569'),
(643, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08201'),
(644, 'Sagàs', 'Berguedà', 'Barcelona', '08619'),
(645, 'Salàs de Pallars', 'Pallars Jussà', 'Lleida', '25693'),
(646, 'Saldes', 'Berguedà', 'Barcelona', '08697'),
(647, 'Sales de Llierca', 'Garrotxa', 'Girona', '17853'),
(648, 'Sallent', 'Bages', 'Barcelona', '08650'),
(649, 'Salomó', 'Tarragonès', 'Tarragona', '43885'),
(650, 'Salou', 'Tarragonès', 'Tarragona', '43840'),
(651, 'Salt', 'Gironès', 'Girona', '17190'),
(652, 'Sanaüja', 'Segarra', 'Lleida', '25753'),
(653, 'Sant Adrià de Besòs', 'Barcelonès', 'Barcelona', '08930'),
(654, 'Sant Agustí de Lluçanès', 'Osona', 'Barcelona', '08586'),
(655, 'Sant Andreu de la Barca', 'Baix Llobregat', 'Barcelona', '08740'),
(656, 'Sant Andreu de Llavaneres', 'Maresme', 'Barcelona', '08392'),
(657, 'Sant Andreu Salou', 'Gironès', 'Girona', '17455'),
(658, 'Sant Aniol de Finestres', 'Garrotxa', 'Girona', '17154'),
(659, 'Sant Antoni de Vilamajor', 'Vallès Oriental', 'Barcelona', '08459'),
(660, 'Sant Bartomeu del Grau', 'Osona', 'Barcelona', '08503'),
(661, 'Sant Boi de Llobregat', 'Baix Llobregat', 'Barcelona', '08830'),
(662, 'Sant Boi de Lluçanès', 'Osona', 'Barcelona', '08589'),
(663, 'Sant Carles de la Ràpita', 'Montsià', 'Tarragona', '43540'),
(664, 'Sant Cebrià de Vallalta', 'Maresme', 'Barcelona', '08396'),
(665, 'Sant Celoni', 'Vallès Oriental', 'Barcelona', '08470'),
(666, 'Sant Climent de Llobregat', 'Baix Llobregat', 'Barcelona', '08849'),
(667, 'Sant Climent Sescebes', 'Alt Empordà', 'Girona', '17751'),
(668, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08172'),
(669, 'Sant Cugat Sesgarrigues', 'Alt Penedès', 'Barcelona', '08798'),
(670, 'Sant Esteve de la Sarga', 'Pallars Jussà', 'Lleida', '25632'),
(671, 'Sant Esteve de Palautordera', 'Vallès Oriental', 'Barcelona', '08461'),
(672, 'Sant Esteve Sesrovires', 'Baix Llobregat', 'Barcelona', '08635'),
(673, 'Sant Feliu de Buixalleu', 'Selva', 'Girona', '17451'),
(674, 'Sant Feliu de Codines', 'Vallès Oriental', 'Barcelona', '08182'),
(675, 'Sant Feliu de Guíxols', 'Baix Empordà', 'Girona', '17220'),
(676, 'Sant Feliu de Llobregat', 'Baix Llobregat', 'Barcelona', '08980'),
(677, 'Sant Feliu de Pallerols', 'Garrotxa', 'Girona', '17174'),
(678, 'Sant Feliu Sasserra', 'Bages', 'Barcelona', '08274'),
(679, 'Sant Ferriol', 'Garrotxa', 'Girona', '17850'),
(680, 'Sant Fost de Campsentelles', 'Vallès Oriental', 'Barcelona', '08105'),
(681, 'Sant Fruitós de Bages', 'Bages', 'Barcelona', '08272'),
(682, 'Sant Gregori', 'Gironès', 'Girona', '17150'),
(683, 'Sant Guim de Freixenet', 'Segarra', 'Lleida', '25270'),
(684, 'Sant Guim de la Plana', 'Segarra', 'Lleida', '25211'),
(685, 'Sant Hilari Sacalm', 'Selva', 'Girona', '17403'),
(686, 'Sant Hipòlit de Voltregà', 'Osona', 'Barcelona', '08512'),
(687, 'Sant Iscle de Vallalta', 'Maresme', 'Barcelona', '08359'),
(688, 'Sant Jaume de Frontanyà', 'Berguedà', 'Barcelona', '08619'),
(689, 'Sant Jaume de Llierca', 'Garrotxa', 'Girona', '17854'),
(690, 'Sant Jaume dels Domenys', 'Baix Penedès', 'Tarragona', '43713'),
(691, 'Sant Jaume d''Enveja', 'Montsià', 'Tarragona', '43877'),
(692, 'Sant Joan de les Abadesses', 'Ripollès', 'Girona', '17860'),
(693, 'Sant Joan de Mollet', 'Gironès', 'Girona', '17463'),
(694, 'Sant Joan de Vilatorrada', 'Bages', 'Barcelona', '08250'),
(695, 'Sant Joan Despí', 'Baix Llobregat', 'Barcelona', '08970'),
(696, 'Sant Joan les Fonts', 'Garrotxa', 'Girona', '17857'),
(697, 'Sant Jordi Desvalls', 'Gironès', 'Girona', '17464'),
(698, 'Sant Julià de Cerdanyola', 'Berguedà', 'Barcelona', '08694'),
(699, 'Sant Julià de Ramis', 'Gironès', 'Girona', '17481'),
(700, 'Sant Julià de Vilatorta', 'Osona', 'Barcelona', '08504'),
(701, 'Sant Julià del Llor i Bonmatí', 'Selva', 'Girona', '17164'),
(702, 'Sant Just Desvern', 'Baix Llobregat', 'Barcelona', '08960'),
(703, 'Sant Llorenç de la Muga', 'Alt Empordà', 'Girona', '17732'),
(704, 'Sant Llorenç de Morunys', 'Solsonès', 'Lleida', '25282'),
(705, 'Sant Llorenç d''Hortons', 'Alt Penedès', 'Barcelona', '08791'),
(706, 'Sant Llorenç Savall', 'Vallès Occidental', 'Barcelona', '08212'),
(707, 'Sant Martí d''Albars', 'Osona', 'Barcelona', '08515'),
(708, 'Sant Martí de Centelles', 'Osona', 'Barcelona', '08592'),
(709, 'Sant Martí de Llémena', 'Gironès', 'Girona', '17153'),
(710, 'Sant Martí de Riucorb', 'Urgell', 'Lleida', '25344'),
(711, 'Sant Martí de Tous', 'Anoia', 'Barcelona', '08712'),
(712, 'Sant Martí Sarroca', 'Alt Penedès', 'Barcelona', '08731'),
(713, 'Sant Martí Sesgueioles', 'Anoia', 'Barcelona', '08282'),
(714, 'Sant Martí Vell', 'Gironès', 'Girona', '17462'),
(715, 'Sant Mateu de Bages', 'Bages', 'Barcelona', '08263'),
(716, 'Sant Miquel de Campmajor', 'Pla de l''Estany', 'Girona', '17831'),
(717, 'Sant Miquel de Fluvià', 'Alt Empordà', 'Girona', '17475'),
(718, 'Sant Mori', 'Alt Empordà', 'Girona', '17467'),
(719, 'Sant Pau de Segúries', 'Ripollès', 'Girona', '17864'),
(720, 'Sant Pere de Ribes', 'Garraf', 'Barcelona', '08810'),
(721, 'Sant Pere de Riudebitlles', 'Alt Penedès', 'Barcelona', '08776'),
(722, 'Sant Pere de Torelló', 'Osona', 'Barcelona', '08572'),
(723, 'Sant Pere de Vilamajor', 'Vallès Oriental', 'Barcelona', '08458'),
(724, 'Sant Pere Pescador', 'Alt Empordà', 'Girona', '17470'),
(725, 'Sant Pere Sallavinera', 'Anoia', 'Barcelona', '08281'),
(726, 'Sant Pol de Mar', 'Maresme', 'Barcelona', '08395'),
(727, 'Sant Quintí de Mediona', 'Alt Penedès', 'Barcelona', '08777'),
(728, 'Sant Quirze de Besora', 'Osona', 'Barcelona', '08580'),
(729, 'Sant Quirze del Vallès', 'Vallès Occidental', 'Barcelona', '08192'),
(730, 'Sant Quirze Safaja', 'Vallès Oriental', 'Barcelona', '08189'),
(731, 'Sant Ramon', 'Segarra', 'Lleida', '25215'),
(732, 'Sant Sadurní d''Anoia', 'Alt Penedès', 'Barcelona', '08770'),
(733, 'Sant Sadurní d''Osormort', 'Osona', 'Barcelona', '08504'),
(734, 'Sant Salvador de Guardiola', 'Bages', 'Barcelona', '08253'),
(735, 'Sant Vicenç de Castellet', 'Bages', 'Barcelona', '08295'),
(736, 'Sant Vicenç de Montalt', 'Maresme', 'Barcelona', '08394'),
(737, 'Sant Vicenç de Torelló', 'Osona', 'Barcelona', '08571'),
(738, 'Sant Vicenç dels Horts', 'Baix Llobregat', 'Barcelona', '08620'),
(739, 'Santa Bàrbara', 'Montsià', 'Tarragona', '43570'),
(740, 'Santa Cecília de Voltregà', 'Osona', 'Barcelona', '08509'),
(741, 'Santa Coloma de Cervelló', 'Baix Llobregat', 'Barcelona', '08690'),
(742, 'Santa Coloma de Farners', 'Selva', 'Girona', '17430'),
(743, 'Santa Coloma de Gramenet', 'Barcelonès', 'Barcelona', '08921'),
(744, 'Santa Coloma de Queralt', 'Conca de Barberà', 'Tarragona', '43420'),
(745, 'Santa Cristina d''Aro', 'Baix Empordà', 'Girona', '17246'),
(746, 'Santa Eugènia de Berga', 'Osona', 'Barcelona', '08507'),
(747, 'Santa Eulàlia de Riuprimer', 'Osona', 'Barcelona', '08519'),
(748, 'Santa Eulàlia de Ronçana', 'Vallès Oriental', 'Barcelona', '08187'),
(749, 'Santa Fe del Penedès', 'Alt Penedès', 'Barcelona', '08792'),
(750, 'Santa Llogaia d''Àlguema', 'Alt Empordà', 'Girona', '17771'),
(751, 'Santa Margarida de Montbui', 'Anoia', 'Barcelona', '08710'),
(752, 'Santa Margarida i els Monjos', 'Alt Penedès', 'Barcelona', '08730'),
(753, 'Santa Maria de Besora', 'Osona', 'Barcelona', '08589'),
(754, 'Santa Maria de Corcó', 'Osona', 'Barcelona', '08511'),
(755, 'Santa Maria de Martorelles', 'Vallès Oriental', 'Barcelona', '08106'),
(756, 'Santa Maria de Merlès', 'Berguedà', 'Barcelona', '08517'),
(757, 'Santa Maria de Miralles', 'Anoia', 'Barcelona', '08787'),
(758, 'Santa Maria de Palautordera', 'Vallès Oriental', 'Barcelona', '08460'),
(759, 'Santa Maria d''Oló', 'Bages', 'Barcelona', '08273'),
(760, 'Santa Oliva', 'Baix Penedès', 'Tarragona', '43710'),
(761, 'Santa Pau', 'Garrotxa', 'Girona', '17811'),
(762, 'Santa Perpètua de Mogoda', 'Vallès Occidental', 'Barcelona', '08130'),
(763, 'Santa Susanna', 'Maresme', 'Barcelona', '08398'),
(764, 'Santpedor', 'Bages', 'Barcelona', '08251'),
(765, 'Sarral', 'Conca de Barberà', 'Tarragona', '43424'),
(766, 'Sarrià de Ter', 'Gironès', 'Girona', '17840'),
(767, 'Sarroca de Bellera', 'Pallars Jussà', 'Lleida', '25555'),
(768, 'Sarroca de Lleida', 'Segrià', 'Lleida', '25175'),
(769, 'Saus, Camallera i Llampaies', 'Alt Empordà', 'Girona', '17465'),
(770, 'Savallà del Comtat', 'Conca de Barberà', 'Tarragona', '43427'),
(771, 'La Secuita', 'Tarragonès', 'Tarragona', '43765'),
(772, 'La Selva de Mar', 'Alt Empordà', 'Girona', '17489'),
(773, 'La Selva del Camp', 'Baix Camp', 'Tarragona', '43470'),
(774, 'Senan', 'Conca de Barberà', 'Tarragona', '43449'),
(775, 'La Sénia', 'Montsià', 'Tarragona', '43560'),
(776, 'Senterada', 'Pallars Jussà', 'Lleida', '25514'),
(777, 'La Sentiu de Sió', 'Noguera', 'Lleida', '25617'),
(778, 'Sentmenat', 'Vallès Occidental', 'Barcelona', '08181'),
(779, 'Serinyà', 'Pla de l''Estany', 'Girona', '17852'),
(780, 'Seròs', 'Segrià', 'Lleida', '25183'),
(781, 'Serra de Daró', 'Baix Empordà', 'Girona', '17133'),
(782, 'Setcases', 'Ripollès', 'Girona', '17869'),
(783, 'La Seu d''Urgell', 'Alt Urgell', 'Lleida', '25700'),
(784, 'Seva', 'Osona', 'Barcelona', '08553'),
(785, 'Sidamon', 'Pla d''Urgell', 'Lleida', '25222'),
(786, 'Sils', 'Selva', 'Girona', '17410'),
(787, 'Sitges', 'Garraf', 'Barcelona', '08870'),
(788, 'Siurana', 'Alt Empordà', 'Girona', '17469'),
(789, 'Sobremunt', 'Osona', 'Barcelona', '08589'),
(790, 'El Soleràs', 'Garrigues', 'Lleida', '25163'),
(791, 'Solivella', 'Conca de Barberà', 'Tarragona', '43412'),
(792, 'Solsona', 'Solsonès', 'Lleida', '25280'),
(793, 'Sora', 'Osona', 'Barcelona', '08588'),
(794, 'Soriguera', 'Pallars Sobirà', 'Lleida', '25566'),
(795, 'Sort', 'Pallars Sobirà', 'Lleida', '25560'),
(796, 'Soses', 'Segrià', 'Lleida', '25181'),
(797, 'Subirats', 'Alt Penedès', 'Barcelona', '08739'),
(798, 'Sudanell', 'Segrià', 'Lleida', '25173'),
(799, 'Sunyer', 'Segrià', 'Lleida', '25174'),
(800, 'Súria', 'Bages', 'Barcelona', '08260'),
(801, 'Susqueda', 'Selva', 'Girona', '17171'),
(802, 'Tagamanent', 'Vallès Oriental', 'Barcelona', '08593'),
(803, 'Talamanca', 'Bages', 'Barcelona', '08279'),
(804, 'Talarn', 'Pallars Jussà', 'Lleida', '25630'),
(805, 'Talavera', 'Segarra', 'Lleida', '25213'),
(806, 'La Tallada d''Empordà', 'Baix Empordà', 'Girona', '17134'),
(807, 'Taradell', 'Osona', 'Barcelona', '08552'),
(808, 'Tarragona', 'Tarragonès', 'Tarragona', '43003'),
(809, 'Tàrrega', 'Urgell', 'Lleida', '25300'),
(810, 'Tarrés', 'Garrigues', 'Lleida', '25480'),
(811, 'Tarroja de Segarra', 'Segarra', 'Lleida', '25211'),
(812, 'Tavèrnoles', 'Osona', 'Barcelona', '08519'),
(813, 'Tavertet', 'Osona', 'Barcelona', '08511'),
(814, 'Teià', 'Maresme', 'Barcelona', '08329'),
(815, 'Térmens', 'Noguera', 'Lleida', '25670'),
(816, 'Terrades', 'Alt Empordà', 'Girona', '17731'),
(817, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08221'),
(818, 'Tiana', 'Maresme', 'Barcelona', '08391'),
(819, 'Tírvia', 'Pallars Sobirà', 'Lleida', '25595'),
(820, 'Tiurana', 'Noguera', 'Lleida', '25791'),
(821, 'Tivenys', 'Baix Ebre', 'Tarragona', '43511'),
(822, 'Tivissa', 'Ribera d''Ebre', 'Tarragona', '43746'),
(823, 'Tona', 'Osona', 'Barcelona', '08551'),
(824, 'Torà', 'Segarra', 'Lleida', '25750'),
(825, 'Tordera', 'Maresme', 'Barcelona', '08490'),
(826, 'Torelló', 'Osona', 'Barcelona', '08570'),
(827, 'Els Torms', 'Garrigues', 'Lleida', '25164'),
(828, 'Tornabous', 'Urgell', 'Lleida', '25331'),
(829, 'La Torre de Cabdella', 'Pallars Jussà', 'Lleida', '25515'),
(830, 'La Torre de Claramunt', 'Anoia', 'Barcelona', '08789'),
(831, 'La Torre de Fontaubella', 'Priorat', 'Tarragona', '43774'),
(832, 'La Torre de l''Espanyol', 'Ribera d''Ebre', 'Tarragona', '43792'),
(833, 'Torrebesses', 'Segrià', 'Lleida', '25176'),
(834, 'Torredembarra', 'Tarragonès', 'Tarragona', '43830'),
(835, 'Torrefarrera', 'Segrià', 'Lleida', '25123'),
(836, 'Torrefeta i Florejacs', 'Segarra', 'Lleida', '25211'),
(837, 'Torregrossa', 'Pla d''Urgell', 'Lleida', '25141'),
(838, 'Torrelameu', 'Noguera', 'Lleida', '25138'),
(839, 'Torrelavit', 'Alt Penedès', 'Barcelona', '08775'),
(840, 'Torrelles de Foix', 'Alt Penedès', 'Barcelona', '08737'),
(841, 'Torrelles de Llobregat', 'Baix Llobregat', 'Barcelona', '08629'),
(842, 'Torrent', 'Baix Empordà', 'Girona', '17123'),
(843, 'Torres de Segre', 'Segrià', 'Lleida', '25170'),
(844, 'Torre-serona', 'Segrià', 'Lleida', '25131'),
(845, 'Torroella de Fluvià', 'Alt Empordà', 'Girona', '17474'),
(846, 'Torroella de Montgrí', 'Baix Empordà', 'Girona', '17257'),
(847, 'Torroja del Priorat', 'Priorat', 'Tarragona', '43737'),
(848, 'Tortellà', 'Garrotxa', 'Girona', '17853'),
(849, 'Tortosa', 'Baix Ebre', 'Tarragona', '43500'),
(850, 'Toses', 'Ripollès', 'Girona', '17536'),
(851, 'Tossa de Mar', 'Selva', 'Girona', '17320'),
(852, 'Tremp', 'Pallars Jussà', 'Lleida', '25620'),
(853, 'Ullà', 'Baix Empordà', 'Girona', '17140'),
(854, 'Ullastrell', 'Vallès Occidental', 'Barcelona', '08231'),
(855, 'Ullastret', 'Baix Empordà', 'Girona', '17114'),
(856, 'Ulldecona', 'Montsià', 'Tarragona', '43550'),
(857, 'Ulldemolins', 'Priorat', 'Tarragona', '43363'),
(858, 'Ultramort', 'Baix Empordà', 'Girona', '17133'),
(859, 'Urús', 'Cerdanya', 'Girona', '17538'),
(860, 'Vacarisses', 'Vallès Occidental', 'Barcelona', '08233'),
(861, 'La Vajol', 'Alt Empordà', 'Girona', '17707'),
(862, 'La Vall de Bianya', 'Garrotxa', 'Girona', '17813'),
(863, 'La Vall de Boí', 'Alta Ribagorça', 'Lleida', '25527'),
(864, 'Vall de Cardós', 'Pallars Sobirà', 'Lleida', '25570'),
(865, 'La Vall d''en Bas', 'Garrotxa', 'Girona', '17176'),
(866, 'Vallbona d''Anoia', 'Anoia', 'Barcelona', '08785'),
(867, 'Vallbona de les Monges', 'Urgell', 'Lleida', '25268'),
(868, 'Vallcebre', 'Berguedà', 'Barcelona', '08699'),
(869, 'Vallclara', 'Conca de Barberà', 'Tarragona', '43439'),
(870, 'Vallfogona de Balaguer', 'Noguera', 'Lleida', '25680'),
(871, 'Vallfogona de Ripollès', 'Ripollès', 'Girona', '17862'),
(872, 'Vallfogona de Riucorb', 'Conca de Barberà', 'Tarragona', '43427'),
(873, 'Vallgorguina', 'Vallès Oriental', 'Barcelona', '08470'),
(874, 'Vallirana', 'Baix Llobregat', 'Barcelona', '08759'),
(875, 'Vall-llobrega', 'Baix Empordà', 'Girona', '17253'),
(876, 'Vallmoll', 'Alt Camp', 'Tarragona', '43144'),
(877, 'Vallromanes', 'Vallès Oriental', 'Barcelona', '08188'),
(878, 'Valls', 'Alt Camp', 'Tarragona', '43800'),
(879, 'Les Valls d''Aguilar', 'Alt Urgell', 'Lleida', '25795'),
(880, 'Les Valls de Valira', 'Alt Urgell', 'Lleida', '25798'),
(881, 'Vandellòs i l''Hospitalet de l''Infant', 'Baix Camp', 'Tarragona', '43891'),
(882, 'La Vansa i Fórnols', 'Alt Urgell', 'Lleida', '25717'),
(883, 'Veciana', 'Anoia', 'Barcelona', '08289'),
(884, 'El Vendrell', 'Baix Penedès', 'Tarragona', '43700'),
(885, 'Ventalló', 'Alt Empordà', 'Girona', '17473'),
(886, 'Verdú', 'Urgell', 'Lleida', '25340'),
(887, 'Verges', 'Baix Empordà', 'Girona', '17142'),
(888, 'Vespella de Gaià', 'Tarragonès', 'Tarragona', '43763'),
(889, 'Vic', 'Osona', 'Barcelona', '08500'),
(890, 'Vidrà', 'Osona', 'Girona', '17515'),
(891, 'Vidreres', 'Selva', 'Girona', '17411'),
(892, 'Vielha e Mijaran', 'Val d''Aran', 'Lleida', '25530'),
(893, 'Vilabella', 'Alt Camp', 'Tarragona', '43886'),
(894, 'Vilabertran', 'Alt Empordà', 'Girona', '17760'),
(895, 'Vilablareix', 'Gironès', 'Girona', '17180'),
(896, 'Vilada', 'Berguedà', 'Barcelona', '08613');
INSERT INTO `m_municipis` (`id`, `municipi`, `comarca`, `provincia`, `cp`) VALUES
(897, 'Viladamat', 'Alt Empordà', 'Girona', '17137'),
(898, 'Viladasens', 'Gironès', 'Girona', '17464'),
(899, 'Viladecans', 'Baix Llobregat', 'Barcelona', '08840'),
(900, 'Viladecavalls', 'Vallès Occidental', 'Barcelona', '08232'),
(901, 'Vilademuls', 'Pla de l''Estany', 'Girona', '17468'),
(902, 'Viladrau', 'Osona', 'Girona', '17406'),
(903, 'Vilafant', 'Alt Empordà', 'Girona', '17740'),
(904, 'Vilafranca del Penedès', 'Alt Penedès', 'Barcelona', '08720'),
(905, 'Vilagrassa', 'Urgell', 'Lleida', '25330'),
(906, 'Vilajuïga', 'Alt Empordà', 'Girona', '17493'),
(907, 'Vilalba dels Arcs', 'Terra Alta', 'Tarragona', '43782'),
(908, 'Vilalba Sasserra', 'Vallès Oriental', 'Barcelona', '08455'),
(909, 'Vilaller', 'Alta Ribagorça', 'Lleida', '25552'),
(910, 'Vilallonga de Ter', 'Ripollès', 'Girona', '17869'),
(911, 'Vilallonga del Camp', 'Tarragonès', 'Tarragona', '43141'),
(912, 'Vilamacolum', 'Alt Empordà', 'Girona', '17474'),
(913, 'Vilamalla', 'Alt Empordà', 'Girona', '17469'),
(914, 'Vilamaniscle', 'Alt Empordà', 'Girona', '17781'),
(915, 'Vilamòs', 'Val d''Aran', 'Lleida', '25551'),
(916, 'Vilanant', 'Alt Empordà', 'Girona', '17743'),
(917, 'Vilanova de Bellpuig', 'Pla d''Urgell', 'Lleida', '25264'),
(918, 'Vilanova de la Barca', 'Segrià', 'Lleida', '25690'),
(919, 'Vilanova de l''Aguda', 'Noguera', 'Lleida', '25749'),
(920, 'Vilanova de Meià', 'Noguera', 'Lleida', '25735'),
(921, 'Vilanova de Prades', 'Conca de Barberà', 'Tarragona', '43439'),
(922, 'Vilanova de Sau', 'Osona', 'Barcelona', '08519'),
(923, 'Vilanova de Segrià', 'Segrià', 'Lleida', '25133'),
(924, 'Vilanova del Camí', 'Anoia', 'Barcelona', '08788'),
(925, 'Vilanova del Vallès', 'Vallès Oriental', 'Barcelona', '08410'),
(926, 'Vilanova d''Escornalbou', 'Baix Camp', 'Tarragona', '43311'),
(927, 'Vilanova i la Geltrú', 'Garraf', 'Barcelona', '08800'),
(928, 'Vilaplana', 'Baix Camp', 'Tarragona', '43380'),
(929, 'Vila-rodona', 'Alt Camp', 'Tarragona', '43814'),
(930, 'Vila-sacra', 'Alt Empordà', 'Girona', '17485'),
(931, 'Vila-sana', 'Pla d''Urgell', 'Lleida', '25245'),
(932, 'Vila-seca', 'Tarragonès', 'Tarragona', '43480'),
(933, 'Vilassar de Dalt', 'Maresme', 'Barcelona', '08339'),
(934, 'Vilassar de Mar', 'Maresme', 'Barcelona', '08340'),
(935, 'Vilaür', 'Alt Empordà', 'Girona', '17483'),
(936, 'Vilaverd', 'Conca de Barberà', 'Tarragona', '43490'),
(937, 'La Vilella Alta', 'Priorat', 'Tarragona', '43375'),
(938, 'La Vilella Baixa', 'Priorat', 'Tarragona', '43374'),
(939, 'Vilobí del Penedès', 'Alt Penedès', 'Barcelona', '08735'),
(940, 'Vilobí d''Onyar', 'Selva', 'Girona', '17185'),
(941, 'Vilopriu', 'Baix Empordà', 'Girona', '17466'),
(942, 'El Vilosell', 'Garrigues', 'Lleida', '25547'),
(943, 'Vimbodí i Poblet', 'Conca de Barberà', 'Tarragona', '43430'),
(944, 'Vinaixa', 'Garrigues', 'Lleida', '25440'),
(945, 'Vinebre', 'Ribera d''Ebre', 'Tarragona', '43792'),
(946, 'Vinyols i els Arcs', 'Baix Camp', 'Tarragona', '43391'),
(947, 'Viver i Serrateix', 'Berguedà', 'Barcelona', '08679'),
(948, 'Xerta', 'Baix Ebre', 'Tarragona', '43592'),
(949, 'L''Estartit', 'Baix Empordà', 'Girona', '17257'),
(950, 'L''Estany', 'Bages', 'Barcelona', '08189'),
(951, 'Barcelona', 'Barcelonès', 'Barcelona', '08000'),
(952, 'Barcelona', 'Barcelonès', 'Barcelona', '08001'),
(953, 'Barcelona', 'Barcelonès', 'Barcelona', '08003'),
(954, 'Barcelona', 'Barcelonès', 'Barcelona', '08004'),
(955, 'Barcelona', 'Barcelonès', 'Barcelona', '08005'),
(956, 'Barcelona', 'Barcelonès', 'Barcelona', '08006'),
(957, 'Barcelona', 'Barcelonès', 'Barcelona', '08007'),
(958, 'Barcelona', 'Barcelonès', 'Barcelona', '08008'),
(959, 'Barcelona', 'Barcelonès', 'Barcelona', '08009'),
(960, 'Barcelona', 'Barcelonès', 'Barcelona', '08010'),
(961, 'Barcelona', 'Barcelonès', 'Barcelona', '08011'),
(962, 'Barcelona', 'Barcelonès', 'Barcelona', '08012'),
(963, 'Barcelona', 'Barcelonès', 'Barcelona', '08013'),
(964, 'Barcelona', 'Barcelonès', 'Barcelona', '08014'),
(965, 'Barcelona', 'Barcelonès', 'Barcelona', '08015'),
(966, 'Barcelona', 'Barcelonès', 'Barcelona', '08016'),
(967, 'Barcelona', 'Barcelonès', 'Barcelona', '08017'),
(968, 'Barcelona', 'Barcelonès', 'Barcelona', '08018'),
(969, 'Barcelona', 'Barcelonès', 'Barcelona', '08019'),
(970, 'Barcelona', 'Barcelonès', 'Barcelona', '08020'),
(971, 'Barcelona', 'Barcelonès', 'Barcelona', '08021'),
(972, 'Barcelona', 'Barcelonès', 'Barcelona', '08022'),
(973, 'Barcelona', 'Barcelonès', 'Barcelona', '08023'),
(974, 'Barcelona', 'Barcelonès', 'Barcelona', '08024'),
(975, 'Barcelona', 'Barcelonès', 'Barcelona', '08025'),
(976, 'Barcelona', 'Barcelonès', 'Barcelona', '08026'),
(977, 'Barcelona', 'Barcelonès', 'Barcelona', '08027'),
(978, 'Barcelona', 'Barcelonès', 'Barcelona', '08028'),
(979, 'Barcelona', 'Barcelonès', 'Barcelona', '08029'),
(980, 'Barcelona', 'Barcelonès', 'Barcelona', '08030'),
(981, 'Barcelona', 'Barcelonès', 'Barcelona', '08031'),
(982, 'Barcelona', 'Barcelonès', 'Barcelona', '08032'),
(983, 'Barcelona', 'Barcelonès', 'Barcelona', '08033'),
(984, 'Barcelona', 'Barcelonès', 'Barcelona', '08034'),
(985, 'Barcelona', 'Barcelonès', 'Barcelona', '08035'),
(986, 'Barcelona', 'Barcelonès', 'Barcelona', '08036'),
(987, 'Barcelona', 'Barcelonès', 'Barcelona', '08037'),
(988, 'Barcelona', 'Barcelonès', 'Barcelona', '08038'),
(989, 'Barcelona', 'Barcelonès', 'Barcelona', '08039'),
(990, 'Barcelona', 'Barcelonès', 'Barcelona', '08040'),
(991, 'Barcelona', 'Barcelonès', 'Barcelona', '08041'),
(992, 'Barcelona', 'Barcelonès', 'Barcelona', '08042'),
(993, 'Barcelona', 'Barcelonès', 'Barcelona', '08043'),
(994, 'Barcelona', 'Barcelonès', 'Barcelona', '08044'),
(995, 'Barcelona', 'Barcelonès', 'Barcelona', '08046'),
(996, 'Barcelona', 'Barcelonès', 'Barcelona', '08050'),
(997, 'Cervelló', 'Baix Llobregat', 'Barcelona', '08054'),
(998, 'Barcelona', 'Barcelonès', 'Barcelona', '08055'),
(999, 'Barcelona', 'Barcelonès', 'Barcelona', '08061'),
(1000, 'Barcelona', 'Barcelonès', 'Barcelona', '08072'),
(1001, 'Barcelona', 'Barcelonès', 'Barcelona', '08080'),
(1002, 'Esparreguera', 'Baix Llobregat', 'Barcelona', '08092'),
(1003, 'Barcelona', 'Barcelonès', 'Barcelona', '08098'),
(1004, 'Martorell', 'Baix Llobregat', 'Barcelona', '08100'),
(1005, 'Sant Fost de Campsentelles', 'Vallès Oriental', 'Barcelona', '08100'),
(1006, 'Palau-solità i Plegamans', 'Vallès Occidental', 'Barcelona', '08104'),
(1007, 'Palau-solità i Plegamans', 'Vallès Occidental', 'Barcelona', '08124'),
(1008, 'Parets del Vallès', 'Vallès Oriental', 'Barcelona', '08160'),
(1009, 'Montornès del Vallès', 'Vallès Oriental', 'Barcelona', '08160'),
(1010, 'Rubí', 'Vallès Occidental', 'Barcelona', '08161'),
(1011, 'Montmeló', 'Vallès Oriental', 'Barcelona', '08170'),
(1012, 'Rubí', 'Vallès Occidental', 'Barcelona', '08171'),
(1013, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08174'),
(1014, 'Barcelona', 'Barcelonès', 'Barcelona', '08000'),
(1015, 'Barcelona', 'Barcelonès', 'Barcelona', '08001'),
(1016, 'Barcelona', 'Barcelonès', 'Barcelona', '08003'),
(1017, 'Barcelona', 'Barcelonès', 'Barcelona', '08004'),
(1018, 'Barcelona', 'Barcelonès', 'Barcelona', '08005'),
(1019, 'Barcelona', 'Barcelonès', 'Barcelona', '08006'),
(1020, 'Barcelona', 'Barcelonès', 'Barcelona', '08007'),
(1021, 'Barcelona', 'Barcelonès', 'Barcelona', '08008'),
(1022, 'Barcelona', 'Barcelonès', 'Barcelona', '08009'),
(1023, 'Barcelona', 'Barcelonès', 'Barcelona', '08010'),
(1024, 'Barcelona', 'Barcelonès', 'Barcelona', '08011'),
(1025, 'Barcelona', 'Barcelonès', 'Barcelona', '08012'),
(1026, 'Barcelona', 'Barcelonès', 'Barcelona', '08013'),
(1027, 'Barcelona', 'Barcelonès', 'Barcelona', '08014'),
(1028, 'Barcelona', 'Barcelonès', 'Barcelona', '08015'),
(1029, 'Barcelona', 'Barcelonès', 'Barcelona', '08016'),
(1030, 'Barcelona', 'Barcelonès', 'Barcelona', '08017'),
(1031, 'Barcelona', 'Barcelonès', 'Barcelona', '08018'),
(1032, 'Barcelona', 'Barcelonès', 'Barcelona', '08019'),
(1033, 'Barcelona', 'Barcelonès', 'Barcelona', '08020'),
(1034, 'Barcelona', 'Barcelonès', 'Barcelona', '08021'),
(1035, 'Barcelona', 'Barcelonès', 'Barcelona', '08022'),
(1036, 'Barcelona', 'Barcelonès', 'Barcelona', '08023'),
(1037, 'Barcelona', 'Barcelonès', 'Barcelona', '08024'),
(1038, 'Barcelona', 'Barcelonès', 'Barcelona', '08025'),
(1039, 'Barcelona', 'Barcelonès', 'Barcelona', '08026'),
(1040, 'Barcelona', 'Barcelonès', 'Barcelona', '08027'),
(1041, 'Barcelona', 'Barcelonès', 'Barcelona', '08028'),
(1042, 'Barcelona', 'Barcelonès', 'Barcelona', '08029'),
(1043, 'Barcelona', 'Barcelonès', 'Barcelona', '08030'),
(1044, 'Barcelona', 'Barcelonès', 'Barcelona', '08031'),
(1045, 'Barcelona', 'Barcelonès', 'Barcelona', '08032'),
(1046, 'Barcelona', 'Barcelonès', 'Barcelona', '08033'),
(1047, 'Barcelona', 'Barcelonès', 'Barcelona', '08034'),
(1048, 'Barcelona', 'Barcelonès', 'Barcelona', '08035'),
(1049, 'Barcelona', 'Barcelonès', 'Barcelona', '08036'),
(1050, 'Barcelona', 'Barcelonès', 'Barcelona', '08037'),
(1051, 'Barcelona', 'Barcelonès', 'Barcelona', '08038'),
(1052, 'Barcelona', 'Barcelonès', 'Barcelona', '08039'),
(1053, 'Barcelona', 'Barcelonès', 'Barcelona', '08040'),
(1054, 'Barcelona', 'Barcelonès', 'Barcelona', '08041'),
(1055, 'Barcelona', 'Barcelonès', 'Barcelona', '08042'),
(1056, 'Barcelona', 'Barcelonès', 'Barcelona', '08043'),
(1057, 'Barcelona', 'Barcelonès', 'Barcelona', '08044'),
(1058, 'Barcelona', 'Barcelonès', 'Barcelona', '08046'),
(1059, 'Barcelona', 'Barcelonès', 'Barcelona', '08050'),
(1060, 'Cervelló', 'Baix Llobregat', 'Barcelona', '08054'),
(1061, 'Barcelona', 'Barcelonès', 'Barcelona', '08055'),
(1062, 'Barcelona', 'Barcelonès', 'Barcelona', '08061'),
(1063, 'Barcelona', 'Barcelonès', 'Barcelona', '08072'),
(1064, 'Barcelona', 'Barcelonès', 'Barcelona', '08080'),
(1065, 'Esparreguera', 'Baix Llobregat', 'Barcelona', '08092'),
(1066, 'Barcelona', 'Barcelonès', 'Barcelona', '08098'),
(1067, 'Martorell', 'Baix Llobregat', 'Barcelona', '08100'),
(1068, 'Sant Fost de Campsentelles', 'Vallès Oriental', 'Barcelona', '08100'),
(1069, 'Palau-solità i Plegamans', 'Vallès Occidental', 'Barcelona', '08104'),
(1070, 'Palau-solità i Plegamans', 'Vallès Occidental', 'Barcelona', '08124'),
(1071, 'Parets del Vallès', 'Vallès Oriental', 'Barcelona', '08160'),
(1072, 'Montornès del Vallès', 'Vallès Oriental', 'Barcelona', '08160'),
(1073, 'Rubí', 'Vallès Occidental', 'Barcelona', '08161'),
(1074, 'Montmeló', 'Vallès Oriental', 'Barcelona', '08170'),
(1075, 'Rubí', 'Vallès Occidental', 'Barcelona', '08171'),
(1076, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08174'),
(1077, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08190'),
(1078, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08191'),
(1079, 'El Bruc', 'Anoia', 'Barcelona', '08194'),
(1080, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08195'),
(1081, 'Lliçà d''Amunt', 'Vallès Oriental', 'Barcelona', '08196'),
(1082, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08197'),
(1083, 'Sant Cugat del Vallès', 'Vallès Occidental', 'Barcelona', '08198'),
(1084, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08200'),
(1085, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08202'),
(1086, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08203'),
(1087, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08204'),
(1088, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08205'),
(1089, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08206'),
(1090, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08207'),
(1091, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08208'),
(1092, 'Sabadell', 'Vallès Occidental', 'Barcelona', '08209'),
(1093, 'Sabadell', 'Vallès Occidental', 'Barcelona', '0820X'),
(1094, 'Badia del Vallès', 'Vallès Occidental', 'Barcelona', '08210'),
(1095, 'Sant Quirze del Vallès', 'Vallès Occidental', 'Barcelona', '08211'),
(1096, 'Barberà del Vallès', 'Vallès Occidental', 'Barcelona', '08220'),
(1097, 'Esplugues de Llobregat', 'Baix Llobregat', 'Barcelona', '08220'),
(1098, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08222'),
(1099, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08223'),
(1100, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08224'),
(1101, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08225'),
(1102, 'Vacarisses', 'Vallès Occidental', 'Barcelona', '08225'),
(1103, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08226'),
(1104, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08227'),
(1105, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08228'),
(1106, 'Terrassa', 'Vallès Occidental', 'Barcelona', '08229'),
(1107, 'Terrassa', 'Vallès Occidental', 'Barcelona', '0822X'),
(1108, 'Viladecavalls', 'Vallès Occidental', 'Barcelona', '08234'),
(1109, 'Manresa', 'Bages', 'Barcelona', '08240'),
(1110, 'Ripollet', 'Vallès Occidental', 'Barcelona', '08241'),
(1111, 'Manresa', 'Bages', 'Barcelona', '08242'),
(1112, 'Manresa', 'Bages', 'Barcelona', '08243'),
(1113, 'Cabrils', 'Maresme', 'Barcelona', '08248'),
(1114, 'Castellgalí', 'Bages', 'Barcelona', '08252'),
(1115, 'Rajadell', 'Bages', 'Barcelona', '08256'),
(1116, 'Riner', 'Solsonès', 'Lleida', '08269'),
(1117, 'Clariana de Cardener', 'Solsonès', 'Lleida', '08269'),
(1118, 'Calders', 'Bages', 'Barcelona', '08279'),
(1119, 'Pujalt', 'Anoia', 'Barcelona', '08281'),
(1120, 'Badia del Vallès', 'Vallès Occidental', 'Barcelona', '08291'),
(1121, 'Pineda de Mar', 'Maresme', 'Barcelona', '08297'),
(1122, 'Vacarisses', 'Vallès Occidental', 'Barcelona', '08299'),
(1123, 'Mataró', 'Maresme', 'Barcelona', '08302'),
(1124, 'Mataró', 'Maresme', 'Barcelona', '08303'),
(1125, 'Mataró', 'Maresme', 'Barcelona', '08304'),
(1126, 'Mataró', 'Maresme', 'Barcelona', '08305'),
(1127, 'Mataró', 'Maresme', 'Barcelona', '0830X'),
(1128, 'Sitges', 'Garraf', 'Barcelona', '08310'),
(1129, 'Òrrius', 'Maresme', 'Barcelona', '08319'),
(1130, 'Alella', 'Maresme', 'Barcelona', '08324'),
(1131, 'Premià de Dalt', 'Maresme', 'Barcelona', '08332'),
(1132, 'Cabrera de Mar', 'Maresme', 'Barcelona', '08340'),
(1133, 'Arenys de Munt', 'Maresme', 'Barcelona', '08353'),
(1134, 'Lloret de Mar', 'Selva', 'Girona', '08360'),
(1135, 'Sant Esteve Sesrovires', 'Baix Llobregat', 'Barcelona', '08365'),
(1136, 'Palafolls', 'Maresme', 'Barcelona', '08385'),
(1137, 'Palafolls', 'Maresme', 'Barcelona', '08387'),
(1138, 'Tordera', 'Maresme', 'Barcelona', '08388'),
(1139, 'Premià de Mar', 'Maresme', 'Barcelona', '08390'),
(1140, 'Sant Adrià de Besòs', 'Barcelonès', 'Barcelona', '08390'),
(1141, 'Tordera', 'Maresme', 'Barcelona', '08397'),
(1142, 'Tordera', 'Maresme', 'Barcelona', '08399'),
(1143, 'Granollers', 'Vallès Oriental', 'Barcelona', '08400'),
(1144, 'Sant Esteve de Palautordera', 'Vallès Oriental', 'Barcelona', '08401'),
(1145, 'Granollers', 'Vallès Oriental', 'Barcelona', '08402'),
(1146, 'Granollers', 'Vallès Oriental', 'Barcelona', '08403'),
(1147, 'Bigues i Riells', 'Vallès Oriental', 'Barcelona', '08410'),
(1148, 'Bigues i Riells', 'Vallès Oriental', 'Barcelona', '08416'),
(1149, 'Vilalba Sasserra', 'Vallès Oriental', 'Barcelona', '08450'),
(1150, 'Sant Esteve de Palautordera', 'Vallès Oriental', 'Barcelona', '08460'),
(1151, 'Sant Celoni', 'Vallès Oriental', 'Barcelona', '08460'),
(1152, 'Montseny', 'Vallès Oriental', 'Barcelona', '08469'),
(1153, 'Vallgorguina', 'Vallès Oriental', 'Barcelona', '08471'),
(1154, 'Gualba', 'Vallès Oriental', 'Barcelona', '08490'),
(1155, 'Calldetenes', 'Osona', 'Barcelona', '08506'),
(1156, 'Gurb', 'Osona', 'Barcelona', '08518'),
(1157, 'Santa Eugènia de Berga', 'Osona', 'Barcelona', '08519'),
(1158, 'Olost', 'Osona', 'Barcelona', '08519'),
(1159, 'Gurb', 'Osona', 'Barcelona', '08519'),
(1160, 'Les Masies de Roda', 'Osona', 'Barcelona', '08519'),
(1161, 'Viladrau', 'Osona', 'Girona', '08553'),
(1162, 'Espinelves', 'Osona', 'Girona', '08553'),
(1163, 'Sant Pere de Torelló', 'Osona', 'Barcelona', '0857'),
(1164, 'Montesquiu', 'Osona', 'Barcelona', '08589'),
(1165, 'Vidrà', 'Osona', 'Girona', '08589'),
(1166, 'Manlleu', 'Osona', 'Barcelona', '08590'),
(1167, 'Berga', 'Berguedà', 'Barcelona', '08601'),
(1168, 'Berga', 'Berguedà', 'Barcelona', '08602'),
(1169, 'Barberà del Vallès', 'Vallès Occidental', 'Barcelona', '08614'),
(1170, 'Sant Esteve de Palautordera', 'Vallès Oriental', 'Barcelona', '08641'),
(1171, 'Olesa de Montserrat', 'Baix Llobregat', 'Barcelona', '08690'),
(1172, 'Cercs', 'Berguedà', 'Barcelona', '08695'),
(1173, 'Cercs', 'Berguedà', 'Barcelona', '08699'),
(1174, 'Gósol', 'Berguedà', 'Lleida', '08699'),
(1175, 'Sant Pere de Ribes', 'Garraf', 'Barcelona', '08713'),
(1176, 'Santa Maria de Miralles', 'Anoia', 'Barcelona', '08719'),
(1177, 'Mediona', 'Alt Penedès', 'Barcelona', '08733'),
(1178, 'Font-rubí', 'Alt Penedès', 'Barcelona', '08739'),
(1179, 'Olesa de Bonesvalls', 'Alt Penedès', 'Barcelona', '08739'),
(1180, 'Torrelles de Foix', 'Alt Penedès', 'Barcelona', '08739'),
(1181, 'Pontons', 'Alt Penedès', 'Barcelona', '08739'),
(1182, 'Esplugues de Llobregat', 'Baix Llobregat', 'Barcelona', '08750'),
(1183, 'Martorell', 'Baix Llobregat', 'Barcelona', '08758'),
(1184, 'Mataró', 'Maresme', 'Barcelona', '08803'),
(1185, 'Barberà del Vallès', 'Vallès Occidental', 'Barcelona', '08810'),
(1186, 'Roquetes', 'Baix Ebre', 'Tarragona', '08812'),
(1187, 'Sant Pere de Ribes', 'Garraf', 'Barcelona', '08812'),
(1188, 'Olivella', 'Garraf', 'Barcelona', '08818'),
(1189, 'Sant Feliu de Llobregat', 'Baix Llobregat', 'Barcelona', '08830'),
(1190, 'Viladecans', 'Baix Llobregat', 'Barcelona', '08844'),
(1191, 'Begues', 'Baix Llobregat', 'Barcelona', '08856'),
(1192, 'Masquefa', 'Anoia', 'Barcelona', '08873'),
(1193, 'Mataró', 'Maresme', 'Barcelona', '08900'),
(1194, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08902'),
(1195, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08903'),
(1196, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08904'),
(1197, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08905'),
(1198, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08906'),
(1199, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08907'),
(1200, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08908'),
(1201, 'L''Hospitalet de Llobregat', 'Barcelonès', 'Barcelona', '08909'),
(1202, 'Argentona', 'Maresme', 'Barcelona', '08910'),
(1203, 'Badalona', 'Barcelonès', 'Barcelona', '08910'),
(1204, 'Badalona', 'Barcelonès', 'Barcelona', '08912'),
(1205, 'Badalona', 'Barcelonès', 'Barcelona', '08913'),
(1206, 'Badalona', 'Barcelonès', 'Barcelona', '08914'),
(1207, 'Badalona', 'Barcelonès', 'Barcelona', '08915'),
(1208, 'Cabrils', 'Maresme', 'Barcelona', '08916'),
(1209, 'Badalona', 'Barcelonès', 'Barcelona', '08916'),
(1210, 'Badalona', 'Barcelonès', 'Barcelona', '08917'),
(1211, 'Badalona', 'Barcelonès', 'Barcelona', '08918'),
(1212, 'Santa Coloma de Gramenet', 'Barcelonès', 'Barcelona', '08920'),
(1213, 'Santa Coloma de Gramenet', 'Barcelonès', 'Barcelona', '08922'),
(1214, 'Santa Coloma de Gramenet', 'Barcelonès', 'Barcelona', '08923'),
(1215, 'Santa Coloma de Gramenet', 'Barcelonès', 'Barcelona', '08924'),
(1216, 'Martorell', 'Baix Llobregat', 'Barcelona', '08926'),
(1217, 'Montgat', 'Maresme', 'Barcelona', '08930'),
(1218, 'Cabrils', 'Maresme', 'Barcelona', '08948'),
(1219, 'Aiguafreda', 'Vallès Oriental', 'Barcelona', '08951'),
(1220, 'Vallgorguina', 'Vallès Oriental', 'Barcelona', '08971'),
(1221, 'Esparreguera', 'Baix Llobregat', 'Barcelona', '08972'),
(1222, 'Pacs del Penedès', 'Alt Penedès', 'Barcelona', '08975'),
(1223, 'Sant Joan Despí', 'Baix Llobregat', 'Barcelona', '08977'),
(1224, 'Piera', 'Anoia', 'Barcelona', '08987'),
(1225, 'Sant Andreu de la Barca', 'Baix Llobregat', 'Barcelona', '08992'),
(1226, 'Sant Pere Sallavinera', 'Anoia', 'Barcelona', '08XXX'),
(1227, 'Figueres', 'Alt Empordà', 'Girona', '17000'),
(1228, 'Palafolls', 'Maresme', 'Barcelona', '17000'),
(1229, 'Girona', 'Gironès', 'Girona', '17001'),
(1230, 'Girona', 'Gironès', 'Girona', '17002'),
(1231, 'Girona', 'Gironès', 'Girona', '17003'),
(1232, 'Girona', 'Gironès', 'Girona', '17005'),
(1233, 'Girona', 'Gironès', 'Girona', '17006'),
(1234, 'Girona', 'Gironès', 'Girona', '17007'),
(1235, 'Girona', 'Gironès', 'Girona', '1700X'),
(1236, 'Girona', 'Gironès', 'Girona', '17010'),
(1237, 'Borrassà', 'Alt Empordà', 'Girona', '17017'),
(1238, 'Castell-Platja d''Aro', 'Baix Empordà', 'Girona', '17048'),
(1239, 'Verges', 'Baix Empordà', 'Girona', '17052'),
(1240, 'Agullana', 'Alt Empordà', 'Girona', '17070'),
(1241, 'Llançà', 'Alt Empordà', 'Girona', '17130'),
(1242, 'Ullastret', 'Baix Empordà', 'Girona', '17133'),
(1243, 'Vilobí d''Onyar', 'Selva', 'Girona', '17135'),
(1244, 'Sant Gregori', 'Gironès', 'Girona', '17151'),
(1245, 'Verges', 'Baix Empordà', 'Girona', '17152'),
(1246, 'Montfulla', 'Gironès', 'Girona', '17180'),
(1247, 'Vilablareix', 'Gironès', 'Girona', '17186'),
(1248, 'Cassà de la Selva', 'Gironès', 'Girona', '17240'),
(1249, 'Castell-Platja d''Aro', 'Baix Empordà', 'Girona', '17248'),
(1250, 'Castell-Platja d''Aro', 'Baix Empordà', 'Girona', '17249'),
(1251, 'Cassà de la Selva', 'Gironès', 'Girona', '17249'),
(1252, 'Sant Antoni de Calonge', 'Baix Empordà', 'Girona', '17252'),
(1253, 'Begur', 'Baix Empordà', 'Girona', '17253'),
(1254, 'Sant Antoni de Calonge', 'Baix Empordà', 'Girona', '17255'),
(1255, 'L''Estartit', 'Baix Empordà', 'Girona', '17258'),
(1256, 'Torroella de Montgrí', 'Baix Empordà', 'Girona', '17258'),
(1257, 'Lloret de Mar', 'Selva', 'Girona', '17300'),
(1258, 'Lloret de Mar', 'Selva', 'Girona', '17301'),
(1259, 'Sant Gregori', 'Gironès', 'Girona', '17350'),
(1260, 'Arbúcies', 'Selva', 'Girona', '17410'),
(1261, 'Castelló d''Empúries', 'Alt Empordà', 'Girona', '17426'),
(1262, 'Santa Eulàlia de Ronçana', 'Vallès Oriental', 'Barcelona', '17446'),
(1263, 'Cadaqués', 'Alt Empordà', 'Girona', '17469'),
(1264, 'Colera', 'Alt Empordà', 'Girona', '17469'),
(1265, 'Planoles', 'Ripollès', 'Girona', '17500'),
(1266, 'Fornells de la Selva', 'Gironès', 'Girona', '17548'),
(1267, 'Vilafant', 'Alt Empordà', 'Girona', '17680'),
(1268, 'Sant Esteve Sesrovires', 'Baix Llobregat', 'Barcelona', '17776'),
(1269, 'Sarrià de Ter', 'Gironès', 'Girona', '17841'),
(1270, 'Castell-Platja d''Aro', 'Baix Empordà', 'Girona', '17853'),
(1271, 'La Vall d''en Bas', 'Garrotxa', 'Girona', '17858'),
(1272, 'Vilamaniscle', 'Alt Empordà', 'Girona', '17881'),
(1273, 'Peralada', 'Alt Empordà', 'Girona', '17941'),
(1274, 'Rabós', 'Alt Empordà', 'Girona', '17XXX'),
(1275, 'Lleida', 'Segrià', 'Lleida', '25001'),
(1276, 'Lleida', 'Segrià', 'Lleida', '25002'),
(1277, 'Lleida', 'Segrià', 'Lleida', '25003'),
(1278, 'Lleida', 'Segrià', 'Lleida', '25004'),
(1279, 'Lleida', 'Segrià', 'Lleida', '25005'),
(1280, 'Lleida', 'Segrià', 'Lleida', '25006'),
(1281, 'Lleida', 'Segrià', 'Lleida', '25008'),
(1282, 'Lleida', 'Segrià', 'Lleida', '25010'),
(1283, 'Lleida', 'Segrià', 'Lleida', '25080'),
(1284, 'Torrefarrera', 'Segrià', 'Lleida', '25126'),
(1285, 'Almatret', 'Segrià', 'Lleida', '25179'),
(1286, 'Lleida', 'Segrià', 'Lleida', '25191'),
(1287, 'Lleida', 'Segrià', 'Lleida', '25192'),
(1288, 'Lleida', 'Segrià', 'Lleida', '25193'),
(1289, 'Llívia', 'Cerdanya', 'Girona', '25195'),
(1290, 'Lleida', 'Segrià', 'Lleida', '25196'),
(1291, 'Lleida', 'Segrià', 'Lleida', '25198'),
(1292, 'Lleida', 'Segrià', 'Lleida', '25199'),
(1293, 'Montoliu de Segarra', 'Segarra', 'Lleida', '25200'),
(1294, 'Pujalt', 'Anoia', 'Barcelona', '25568'),
(1295, 'Vall de Cardós', 'Pallars Sobirà', 'Lleida', '25571'),
(1296, 'Aitona', 'Segrià', 'Lleida', '25582'),
(1297, 'Vielha e Mijaran', 'Val d''Aran', 'Lleida', '25717'),
(1298, 'Esterri d''Àneu', 'Pallars Sobirà', 'Lleida', '25980'),
(1299, 'Lleida', 'Segrià', 'Lleida', '25XXX'),
(1300, 'Gósol', 'Berguedà', 'Lleida', '25XXX'),
(1301, 'Massalcoreig', 'Segrià', 'Lleida', '25XXX'),
(1302, 'Naut Aran', 'Val d''Aran', 'Lleida', '25XXX'),
(1303, 'Reus', 'Baix Camp', 'Tarragona', '43000'),
(1304, 'Tarragona', 'Tarragonès', 'Tarragona', '43000'),
(1305, 'Tarragona', 'Tarragonès', 'Tarragona', '43001'),
(1306, 'Tarragona', 'Tarragonès', 'Tarragona', '43002'),
(1307, 'Tarragona', 'Tarragonès', 'Tarragona', '43004'),
(1308, 'Tarragona', 'Tarragonès', 'Tarragona', '43005'),
(1309, 'Deltebre', 'Baix Ebre', 'Tarragona', '43006'),
(1310, 'Cambrils', 'Baix Camp', 'Tarragona', '43006'),
(1311, 'Tarragona', 'Tarragonès', 'Tarragona', '43006'),
(1312, 'Tarragona', 'Tarragonès', 'Tarragona', '43007'),
(1313, 'Tarragona', 'Tarragonès', 'Tarragona', '43008'),
(1314, 'Cambrils', 'Baix Camp', 'Tarragona', '43080'),
(1315, 'Vila-seca', 'Tarragonès', 'Tarragona', '43080'),
(1316, 'La Canonja', 'Tarragonès', 'Tarragona', '43100'),
(1317, 'Tarragona', 'Tarragonès', 'Tarragona', '43106'),
(1318, 'Reus', 'Baix Camp', 'Tarragona', '43200'),
(1319, 'Reus', 'Baix Camp', 'Tarragona', '43202'),
(1320, 'Reus', 'Baix Camp', 'Tarragona', '43203'),
(1321, 'Reus', 'Baix Camp', 'Tarragona', '43204'),
(1322, 'Reus', 'Baix Camp', 'Tarragona', '43205'),
(1323, 'Reus', 'Baix Camp', 'Tarragona', '43206'),
(1324, 'Reus', 'Baix Camp', 'Tarragona', '4320X'),
(1325, 'Solivella', 'Conca de Barberà', 'Tarragona', '43421'),
(1326, 'Pontils', 'Conca de Barberà', 'Tarragona', '43426'),
(1327, 'Sant Carles de la Ràpita', 'Montsià', 'Tarragona', '43450'),
(1328, 'Salou', 'Tarragonès', 'Tarragona', '43480'),
(1329, 'Amposta', 'Montsià', 'Tarragona', '43540'),
(1330, 'Sant Carles de la Ràpita', 'Montsià', 'Tarragona', '43549'),
(1331, 'Ulldecona', 'Montsià', 'Tarragona', '43559'),
(1332, 'Alcover', 'Alt Camp', 'Tarragona', '43600'),
(1333, 'Reus', 'Baix Camp', 'Tarragona', '43611'),
(1334, 'Amposta', 'Montsià', 'Tarragona', '43780'),
(1335, 'Creixell', 'Tarragonès', 'Tarragona', '43838'),
(1336, 'Calafell', 'Baix Penedès', 'Tarragona', '43882'),
(1337, 'Amposta', 'Montsià', 'Tarragona', '48870'),
(1338, 'Navès', 'Solsonès', 'Lleida', '81070'),
(1339, '', '', 'Altres', '');

-- --------------------------------------------------------

--
-- Estructura de la taula `m_nacions`
--

CREATE TABLE IF NOT EXISTS `m_nacions` (
  `codi` varchar(3) COLLATE latin1_spanish_ci NOT NULL,
  `pais` varchar(50) COLLATE latin1_spanish_ci NOT NULL,
  PRIMARY KEY (`codi`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;

--
-- Bolcant dades de la taula `m_nacions`
--

INSERT INTO `m_nacions` (`codi`, `pais`) VALUES
('AFG', 'Afganistan'),
('ALA', 'Åland, illes, Aland, illes'),
('ALB', 'Albània'),
('DEU', 'Alemanya'),
('DZA', 'Algèria'),
('AND', 'Andorra'),
('AGO', 'Angola'),
('AIA', 'Anguilla'),
('ATA', 'Antàrtida'),
('ATG', 'Antigua i Barbuda'),
('ANT', 'Antilles Neerlandeses, Antilles Holandeses'),
('SAU', 'Aràbia Saudita'),
('ARG', 'Argentina'),
('ARM', 'Armènia'),
('ABW', 'Aruba'),
('AUS', 'Austràlia'),
('AUT', 'Àustria'),
('AZE', 'Azerbaidjan, Azerbaitjan'),
('BHS', 'Bahames'),
('BHR', 'Bahrain'),
('BGD', 'Bangla Desh, Bangladesh'),
('BRB', 'Barbados'),
('BEL', 'Bèlgica'),
('BLZ', 'Belize'),
('BEN', 'Benín'),
('BMU', 'Bermudes, Bermuda, Bermudes, les'),
('BTN', 'Bhutan'),
('BLR', 'Bielorússia'),
('BOL', 'Bolívia'),
('BIH', 'Bòsnia i Hercegovina'),
('BWA', 'Botswana'),
('BVT', 'Bouvet, Bouvet, illa'),
('BRA', 'Brasil'),
('BRN', 'Brunei'),
('BGR', 'Bulgària'),
('BFA', 'Burkina Faso'),
('BDI', 'Burundi'),
('CYM', 'Caiman, illes, Caiman, les'),
('KHM', 'Cambodja, Cambotja'),
('CMR', 'Camerun'),
('CAN', 'Canadà'),
('CPV', 'Cap Verd'),
('CAF', 'Centreafricana, República, Centrafricana, Repúblic'),
('CXR', 'Christmas, illa'),
('CCK', 'Cocos, illes, Cocos (Keeling), illes'),
('COL', 'Colòmbia'),
('COM', 'Comores'),
('COG', 'Congo, República del'),
('COD', 'Congo, República Democràtica del'),
('COK', 'Cook, illes'),
('PRK', 'Corea del Nord, Corea, República Democràtica Popul'),
('KOR', 'Corea del Sud, Corea, República de'),
('CIV', 'Costa d''Ivori'),
('CRI', 'Costa Rica'),
('HRV', 'Croàcia'),
('CUB', 'Cuba'),
('DNK', 'Dinamarca'),
('DJI', 'Djibouti'),
('DMA', 'Dominica'),
('DOM', 'Dominicana, República'),
('EGY', 'Egipte'),
('ECU', 'Equador'),
('ARE', 'Emirats Àrabs Units, Unió dels Emirats Àrabs'),
('ERI', 'Eritrea'),
('SVK', 'Eslovàquia'),
('SVN', 'Eslovènia'),
('ESP', 'Espanya'),
('USA', 'Estats Units (EUA), Estats Units d''Amèrica'),
('EST', 'Estònia'),
('ETH', 'Etiòpia'),
('FRO', 'Fèroe, illes'),
('FJI', 'Fiji'),
('PHL', 'Filipines'),
('FIN', 'Finlàndia'),
('FRA', 'França'),
('GAB', 'Gabon'),
('GMB', 'Gàmbia'),
('GEO', 'Geòrgia'),
('SGS', 'Geòrgia del Sud i Sandwich del Sud, illes'),
('GHA', 'Ghana'),
('GIB', 'Gibraltar'),
('GRC', 'Grècia'),
('GRD', 'Grenada'),
('GRL', 'Grenlàndia, Groenlàndia'),
('GLP', 'Guadeloupe, Guadalupe'),
('GUF', 'Guaiana Francesa, Guaiana Francesa, la'),
('GUM', 'Guam'),
('GTM', 'Guatemala'),
('GIN', 'República de Guinea'),
('GNB', 'Guinea Bissau, Guinea-Bissau'),
('GNQ', 'Guinea Equatorial'),
('GUY', 'Guyana'),
('HTI', 'Haití'),
('HMD', 'Heard, illa i McDonald, illes'),
('HND', 'Hondures'),
('HKG', 'Hong Kong'),
('HUN', 'Hongria'),
('YEM', 'Iemen'),
('UMI', 'Illes Perifèriques Menors dels EUA, Estats Units d'),
('IND', 'Índia'),
('IDN', 'Indonèsia'),
('IRN', 'Iran'),
('IRQ', 'Iraq'),
('IRL', 'Irlanda'),
('ISL', 'Islàndia'),
('ISR', 'Israel'),
('ITA', 'Itàlia'),
('JAM', 'Jamaica'),
('JPN', 'Japó'),
('JOR', 'Jordània'),
('KAZ', 'Kazakhstan'),
('KEN', 'Kenya'),
('KGZ', 'Kirguizistan'),
('KIR', 'Kiribati'),
('KWT', 'Kuwait'),
('LAO', 'Laos'),
('LSO', 'Lesotho'),
('LVA', 'Letònia'),
('LBN', 'Líban'),
('LBR', 'Libèria'),
('LBY', 'Líbia'),
('LIE', 'Liechtenstein'),
('LTU', 'Lituània'),
('LUX', 'Luxemburg'),
('MAC', 'Macau'),
('MKD', 'Macedònia'),
('MDG', 'Madagascar'),
('MYS', 'Malàisia'),
('MWI', 'Malawi'),
('MDV', 'Maldives'),
('MLI', 'Mali'),
('MLT', 'Malta'),
('FLK', 'Malvines, illes, Malvines (Falkland), illes'),
('MNP', 'Mariannes Septentrionals, illes, Mariannes del Nor'),
('MAR', 'Marroc'),
('MHL', 'Marshall, illes, Marshall'),
('MTQ', 'Martinica'),
('MUS', 'Maurici'),
('MRT', 'Mauritània'),
('MYT', 'Mayotte'),
('MEX', 'Mèxic'),
('FSM', 'Micronèsia, Estats Federats de'),
('MOZ', 'Moçambic'),
('MDA', 'Moldàvia'),
('MCO', 'Mònaco'),
('MNG', 'Mongòlia'),
('MNE', 'Montenegro'),
('MSR', 'Montserrat'),
('MMR', 'Myanmar, Myanma'),
('NAM', 'Namíbia'),
('NRU', 'Nauru'),
('NPL', 'Nepal'),
('NIC', 'Nicaragua'),
('NER', 'Níger'),
('NGA', 'Nigèria'),
('NIU', 'Niue'),
('NFK', 'Norfolk, illa, Norfolk'),
('NOR', 'Noruega'),
('NCL', 'Nova Caledònia'),
('NZL', 'Nova Zelanda'),
('OMN', 'Oman'),
('NLD', 'Països Baixos'),
('PAK', 'Pakistan'),
('PLW', 'Palau'),
('PSE', 'Palestina, Cisjordània i Gaza'),
('PAN', 'Panamà'),
('PNG', 'Papua Nova Guinea'),
('PRY', 'Paraguai'),
('PER', 'Perú'),
('PCN', 'Pitcairn, illes, Pitcairn'),
('PYF', 'Polinèsia Francesa'),
('POL', 'Polònia'),
('PRT', 'Portugal'),
('PRI', 'Puerto Rico'),
('QAT', 'Qatar'),
('GBR', 'Regne Unit, Gran Bretanya'),
('REU', 'Reunió, illa de la, Reunió, la, Reunion, Réunion'),
('ROU', 'Romania'),
('RUS', 'Rússia'),
('RWA', 'Rwanda'),
('ESH', 'Sàhara Occidental'),
('KNA', 'Saint Kitts i Nevis, Saint Christopher i Nevis'),
('LCA', 'Saint Lucia'),
('SPM', 'Saint-Pierre i Miquelon, Saint Pierre i Miquelon, '),
('VCT', 'Saint Vincent i les Grenadines, Saint Vincent i Gr'),
('SLB', 'Salomó'),
('SLV', 'Salvador, El, Salvador, el'),
('WSM', 'Samoa'),
('ASM', 'Samoa Nord-americana, Samoa Americana'),
('SMR', 'San Marino'),
('SHN', 'Santa Helena, Saint Helena'),
('STP', 'São Tomé i Príncipe, Sao Tomé i Príncipe'),
('SEN', 'Senegal'),
('SRB', 'Sèrbia'),
('SYC', 'Seychelles'),
('SLE', 'Sierra Leone'),
('SGP', 'Singapur'),
('SYR', 'Síria'),
('SOM', 'Somàlia'),
('LKA', 'Sri Lanka'),
('ZAF', 'Sud-àfrica, Sud-àfrica, República de'),
('SDN', 'Sudan'),
('SWE', 'Suècia'),
('CHE', 'Suïssa'),
('SUR', 'Surinam'),
('SJM', 'Svalbard i Jan Mayen'),
('SWZ', 'Swazilàndia'),
('TJK', 'Tadjikistan'),
('THA', 'Tailàndia'),
('TWN', 'Taiwan'),
('TZA', 'Tanzània'),
('IOT', 'Territori Britànic de l''Oceà Índic'),
('ATF', 'Territoris Francesos del Sud, Terres Australs Fran'),
('TLS', 'Timor Oriental, Timor-Leste'),
('TGO', 'Togo'),
('TKL', 'Tokelau'),
('TON', 'Tonga'),
('TTO', 'Trinitat i Tobago'),
('TUN', 'Tunísia'),
('TKM', 'Turkmenistan'),
('TCA', 'Turks i Caicos, illes'),
('TUR', 'Turquia'),
('TUV', 'Tuvalu'),
('TCD', 'Txad'),
('CZE', 'Txeca, República'),
('UKR', 'Ucraïna'),
('UGA', 'Uganda'),
('URY', 'Uruguai'),
('UZB', 'Uzbekistan'),
('VUT', 'Vanuatu'),
('VAT', 'Vaticà, Ciutat del'),
('VEN', 'Veneçuela'),
('VGB', 'Verges Britàniques, illes, Verges, illes (Regne Un'),
('VIR', 'Verges Nord-americanes, illes, Verges Americanes, '),
('VNM', 'Vietnam'),
('WLF', 'Wallis i Futuna'),
('CHL', 'Xile'),
('CHN', 'Xina'),
('CYP', 'Xipre'),
('ZMB', 'Zàmbia'),
('ZWE', 'Zimbabwe');



**************************************************************************************************
*********************************          Sincronització        ********************************* 
**************************************************************************************************

A dades catalunya

Crear taula "m_lastupdate" (ACCESS)

camp: taula, texte  20
camp: lastupdate, fecha/hora, formato general

dada: dadesersonals, null
dade: partes, data actual

Crear taula "m_logerrors" (ACCESS)

camp: id, autonumèric
camp: num, número
camp: descripcio, text 50
camp: sms, text 255
camp: form, text 50
camp: sub, text 50
camp: query, memo	
camp: time, datetime, default now()

Afegir check web, a Parte i [Datos Presonales]. Si/NO valor predeterminado NO (0)

A Catalana

Crear vista "m_categorias_access" 

SELECT categorias.códigosalidacategoria, TipoParte.idTipoparte, categorias.IdCategoria, categorias.[categoria definición]
FROM categorias INNER JOIN TipoParte ON categorias.tipoparte = TipoParte.idTipoparte


Crear Objectes VBA
	m_sincronitzacio
	m_funcions	


Modificacions Formulari Parte

Modificar formulari per afegir camp --> idParte (visible NO)

Modificar formulari per afegir camp --> web


Afegir procediment camp pafed --> AfterUpdate
	Actaulitza camp categoriaParteDet



Modificacions Formulari Parte Detallado 


Modificar vista "PARTE DETALLADO" origen del subform "parte detalaldo"
Per afegir id --> idpartedet

Modificar subformulari per afegir camp --> idpartedet (visible NO)


Modificar Form_BeforeUpdate()  (Està al codi) 
per recollir si és insert o update

Modificacions Formulari Datos Personales Entrada

Modificar Form_AfterInsert  (Està al codi)


Afegir condifició al camp categoriaParteDet

SELECT DISTINCT Categorias.IdCategoria, Categorias.[categoria definición] FROM Categorias 
WHERE Categorias.tipoparte = Forms![parte]![pafed]
ORDER BY Categorias.[categoria definición]; 
