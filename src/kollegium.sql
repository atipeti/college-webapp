SET NAMES utf8 COLLATE utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS teamek (
	team_nev VARCHAR(40),
	leiras TEXT,
	PRIMARY KEY (team_nev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS felhasznalok (
	felhasznalonev VARCHAR(80),
	jelszo VARCHAR(15),
	vezeteknev VARCHAR(20),
	keresztnev VARCHAR(20),
	masodik_nev VARCHAR(20),
	email VARCHAR(40),
	team VARCHAR(40),
	team_szerepkor TINYINT,
	jogosultsag TINYINT,
	utolso_belepes DATETIME,
	PRIMARY KEY (felhasznalonev),
	FOREIGN KEY (team) REFERENCES teamek(team_nev) ON UPDATE CASCADE
	ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS feladatok (
	feladat VARCHAR(40),
	letrehozo VARCHAR(80),
	felelos VARCHAR(80),
	leiras TEXT,
	hatarido DATE,
	statusz VARCHAR(20),
	PRIMARY KEY (feladat),
	FOREIGN KEY (letrehozo) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE SET NULL,
	FOREIGN KEY (felelos) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS esemenyek (
	nev VARCHAR(80),
	leiras TEXT,
	idopont_kezd DATETIME,
	idopont_vege DATETIME,
	alkalmak TINYINT,
	rendszeresseg VARCHAR(20),
	kotelezoseg BOOLEAN,
	meghirdeto VARCHAR(80),
	visszajelzesi_hatarido DATETIME,
	veglegesseg BOOLEAN,
	PRIMARY KEY (nev),
	FOREIGN KEY (meghirdeto) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS esemeny_opciok (
	azonosito INT NOT NULL AUTO_INCREMENT,
	esemeny_nev VARCHAR(80),
	idopont_kezd DATETIME,
	idopont_vege DATETIME,
	PRIMARY KEY (azonosito),
	FOREIGN KEY (esemeny_nev) REFERENCES esemenyek(nev) ON UPDATE CASCADE
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS visszajelzesek (
	azonosito INT NOT NULL AUTO_INCREMENT,
	felhasznalo VARCHAR(80),
	esemeny VARCHAR(80),
	fontossag TINYINT,
	reszvetel BOOLEAN,
	PRIMARY KEY (azonosito),
	FOREIGN KEY (felhasznalo) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE CASCADE,
	FOREIGN KEY (esemeny) REFERENCES esemenyek(nev) ON UPDATE CASCADE
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS forumok (
	cim VARCHAR(80),
	letrehozo VARCHAR(80),
	letrehozas DATETIME,
	modositas DATETIME,
	PRIMARY KEY (cim),
	FOREIGN KEY (letrehozo) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS bejegyzesek (
	azonosito INT NOT NULL AUTO_INCREMENT,
	forum VARCHAR(80),
	targy VARCHAR(40),
	szoveg TEXT,
	kuldo VARCHAR(80),
	idopont TIMESTAMP,
	PRIMARY KEY (azonosito),
	FOREIGN KEY (forum) REFERENCES forumok(cim) ON UPDATE CASCADE
	ON DELETE CASCADE,
	FOREIGN KEY (kuldo) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS egyeni_programok (
	azonosito INT NOT NULL AUTO_INCREMENT,
	nev VARCHAR(80),
	felhasznalo VARCHAR(80),
	idopont_kezd DATETIME,
	idopont_vege DATETIME,
	alkalmak TINYINT,
	rendszeresseg VARCHAR(20),
	fontossag TINYINT,
	PRIMARY KEY (azonosito),
	FOREIGN KEY (felhasznalo) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS kozos_helyisegek (
	helyisegnev VARCHAR(80),
	leiras TEXT,
	ferohely INT,
	PRIMARY KEY (helyisegnev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

CREATE TABLE IF NOT EXISTS kozos_helyiseg_hasznalat (
	azonosito INT NOT NULL AUTO_INCREMENT,
	felhasznalo VARCHAR(80),
	helyiseg VARCHAR(80),
	kezdes DATETIME,
	befejezes DATETIME,
	kizarolagossag BOOLEAN,
	PRIMARY KEY (azonosito),
	FOREIGN KEY (felhasznalo) REFERENCES felhasznalok(felhasznalonev) ON UPDATE CASCADE
	ON DELETE CASCADE,
	FOREIGN KEY (helyiseg) REFERENCES kozos_helyisegek(helyisegnev) ON UPDATE CASCADE
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;


INSERT INTO teamek(team_nev, leiras) VALUES ('Team1', 'Team1 leírása'), ('Team2', 'Team2 leírása'), ('Team3', 'Team3 leírása'), ('Team4', 'Team4 leírása');
INSERT INTO felhasznalok(felhasznalonev, jelszo, vezeteknev, keresztnev, masodik_nev, email, team, team_szerepkor, jogosultsag) VALUES ('igazga.tobias', 'admin1', 'Igazga', 'Tóbiás', NULL, 'igazgatobias@gmail.com', NULL, NULL, 1), ('gipsz.jakab', 'koordinator', 'Gipsz', 'Jakab', NULL, 'gipsz@gmail.com', 'Team1', 1, 2), ('programo.zoltan', 'hallgato', 'Programo', 'Zoltán', NULL, 'programozoltan@gmail.com', 'Team2', 1, 3), ('kovacs.eszter.anett', 'aSd14T', 'Kovács', 'Eszter', 'Anett', 'kovacs.eszter12@gmail.com', 'Team1', 2, 3), ('vicc.elek', 'hAHaXd', 'Vicc', 'Elek', NULL, 'viccelek@gmail.com', 'Team4', 2, 2), ('deriv.almos.tamas', 'y30fLa', 'Deriv', 'Álmos', 'Tamás', 'derivalmos@gmail.com', 'Team3', 1, 3), ('nagy.eva', '5uQP0c', 'Nagy', 'Éva', NULL, 'nagyeva21@gmail.com', 'Team3', 2, 3), ('nagy.eva.1', 'cs28K1', 'Nagy', 'Éva', NULL, 'nagy.evike@gmail.com', 'Team4', 1, 2);
INSERT INTO feladatok(feladat, letrehozo, felelos, leiras, hatarido, statusz) VALUES ('Feladat1', 'igazga.tobias', 'gipsz.jakab', 'Feladat1 leírása', '2019-07-01', 'todo'), ('Feladat2', 'igazga.tobias', 'programo.zoltan', 'Feladat2 leírása', '2019-07-12', 'todo'), ('Feladat3', 'gipsz.jakab', 'kovacs.eszter.anett', 'Feladat3 leírása', '2019-05-14', 'done'), ('Feladat4', 'vicc.elek', 'gipsz.jakab', 'Feladat4 leírása', '2019-06-21', 'doing');
INSERT INTO esemenyek(nev, leiras, idopont_kezd, idopont_vege, alkalmak, rendszeresseg, kotelezoseg, meghirdeto, visszajelzesi_hatarido, veglegesseg) VALUES ('Esemény1', 'Esemény1 leírása', '2019-07-14 16:00:00', '2019-07-14 20:00:00', 1, NULL, 0, 'gipsz.jakab', '2019-07-10 23:00:00', 1), ('Esemény2', 'Esemény2 leírása', NULL, NULL, 2, 'havonta', 1, 'igazga.tobias', '2019-06-18 22:00:00', 0), ('Esemény3', 'Esemény3 leírása', '2019-07-17 12:00:00', '2019-07-17 13:30:00', 2, 'hetente', 1, 'nagy.eva', '2019-07-14 18:00:00', 1), ('Esemény4', 'Esemény4 leírása', NULL, NULL, 1, NULL, 0, 'vicc.elek', '2019-06-17 16:00:00', 0), ('Esemény5', 'Esemény5 leírása', '2019-05-28 17:00:00', '2019-05-28 18:30:00', 1, NULL, 0, 'gipsz.jakab', '2019-05-25 16:00:00', 1), ('Esemény6', 'Esemény6 leírása', NULL, NULL, 1, NULL, 0, 'igazga.tobias', '2019-07-02 12:00:00', 0), ('Esemény7', 'Esemény7 leírása', '2019-03-02 09:00:00', '2019-03-02 15:00:00', 1, NULL, 1, 'igazga.tobias', '2019-02-28 09:00:00', 1);
INSERT INTO esemeny_opciok(esemeny_nev, idopont_kezd, idopont_vege) VALUES ('Esemény6', '2019-07-20 09:00:00', '2019-07-20 15:00:00'), ('Esemény6', '2019-07-21 10:00:00', '2019-07-21 16:00:00'), ('Esemény6', '2019-07-24 09:30:00', '2019-07-24 15:30:00'), ('Esemény6', '2019-07-19 10:00:00', '2019-07-19 16:00:00'), ('Esemény2', '2019-07-05 10:00:00', '2019-07-05 13:00:00'), ('Esemény2', '2019-07-08 13:00:00', '2019-07-08 16:00:00'), ('Esemény2', '2019-07-07 14:30:00', '2019-07-07 17:30:00'), ('Esemény4', '2019-06-19 16:00:00', '2019-06-19 17:30:00'), ('Esemény4', '2019-06-26 15:00:00', '2019-06-26 16:30:00');
INSERT INTO visszajelzesek(felhasznalo, esemeny, fontossag, reszvetel) VALUES ('gipsz.jakab', 'Esemény7', 3, 1), ('vicc.elek', 'Esemény7', NULL, 0), ('gipsz.jakab', 'Esemény6', 2, NULL), ('programo.zoltan', 'Esemény6', 3, NULL), ('kovacs.eszter.anett', 'Esemény6', 1, NULL), ('gipsz.jakab', 'Esemény2', 3, NULL), ('nagy.eva.1', 'Esemény4', 0, NULL), ('nagy.eva', 'Esemény6', 2, NULL), ('vicc.elek', 'Esemény6', 0, NULL), ('nagy.eva.1', 'Esemény2', 3, NULL), ('nagy.eva', 'Esemény2', 3, NULL), ('gipsz.jakab', 'Esemény4', 2, NULL), ('programo.zoltan', 'Esemény3', NULL, 1), ('kovacs.eszter.anett', 'Esemény3', NULL, 0), ('deriv.almos.tamas', 'Esemény6', 3, NULL);
INSERT INTO forumok(cim, letrehozo, letrehozas, modositas) VALUES ('Fórum1', 'nagy.eva.1', '2019-04-14 10:22:42', '2019-04-14 21:12:47'), ('Fórum2', 'programo.zoltan', '2019-05-01 18:06:21', '2019-05-01 18:14:55');
INSERT INTO bejegyzesek(forum, targy, szoveg, kuldo, idopont) VALUES ('Fórum1', 'fórum használata', 'Hogyan kell használni a fórumot?', 'nagy.eva.1', '2019-04-14 10:34:51'), ('Fórum1', NULL, 'Így', 'vicc.elek', '2019-04-14 21:05:11'), ('Fórum1', 'fórum használata', 'Oké, köszi :)', 'nagy.eva.1', '2019-04-14 21:12:47'), ('Fórum2', 'ennek a bejegyzésnek nincs tárgya', 'Ebbe a fórumba ne írjatok pls', 'programo.zoltan', '2019-05-01 18:14:55');
INSERT INTO egyeni_programok(nev, felhasznalo, idopont_kezd, idopont_vege, alkalmak, rendszeresseg, fontossag) VALUES ('Program1', 'igazga.tobias', '2019-05-21 15:00:00', '2019-05-21 17:00:00', 2, 'hetente', 3), ('Program2', 'programo.zoltan', '2019-07-06 10:00:00', '2019-07-06 11:30:00', 3, 'kéthetente', 2), ('Program3', 'kovacs.eszter.anett', '2019-07-19 14:00:00', '2019-07-19 17:30:00', 1, NULL, 2), ('Program4', 'gipsz.jakab', '2019-06-14 16:00:00', '2019-06-14 18:00:00', 1, NULL, 3), ('Program5', 'igazga.tobias', '2019-06-20 13:00:00', '2019-06-20 15:00:00', 1, NULL, 2), ('Program6', 'gipsz.jakab', '2019-07-14 15:00:00', '2019-07-14 17:00:00', 3, 'hetente', 2), ('Program7', 'deriv.almos.tamas', '2019-07-19 11:00:00', '2019-07-19 12:30:00', 2, 'három hetente', 2);
INSERT INTO kozos_helyisegek(helyisegnev, leiras, ferohely) VALUES ('Konditerem', 'Konditerem leírása', 10), ('Mosoda', 'Mosoda leírása', 7);
INSERT INTO kozos_helyiseg_hasznalat(felhasznalo, helyiseg, kezdes, befejezes, kizarolagossag) VALUES ('gipsz.jakab', 'Konditerem', '2019-06-10 17:00:00', '2019-06-10 19:30:00', 0), ('gipsz.jakab', 'Konditerem', '2019-06-17 17:00:00', '2019-06-17 19:30:00', 0), ('gipsz.jakab', 'Konditerem', '2019-06-24 17:00:00', '2019-06-24 18:30:00', 0), ('programo.zoltan', 'Konditerem', '2019-06-10 16:00:00', '2019-06-10 18:00:00', 0), ('vicc.elek', 'Konditerem', '2019-05-28 14:30:00', '2019-05-28 16:30:00', 1), ('gipsz.jakab', 'Mosoda', '2019-06-01 18:00:00', '2019-06-01 18:30:00', 0), ('nagy.eva', 'Mosoda', '2019-06-01 18:00:00', '2019-06-01 18:30:00', 0), ('deriv.almos.tamas', 'Mosoda', '2019-06-01 18:30:00', '2019-06-01 19:00:00', 0), ('kovacs.eszter.anett', 'Mosoda', '2019-06-01 18:00:00', '2019-06-01 18:30:00', 0);