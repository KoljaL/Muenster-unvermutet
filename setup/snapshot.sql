PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE stations (
    id TEXT PRIMARY KEY,
    number INTEGER NOT NULL,
    title TEXT NOT NULL,
    hint TEXT NOT NULL,
    lat NUMERIC NOT NULL,
    lon NUMERIC NOT NULL,
    correct_option_index INTEGER NOT NULL,
    solution_title TEXT NOT NULL,
    solution_caption TEXT,
    info TEXT NOT NULL,
    task_image TEXT NOT NULL,
    solution_image TEXT,
    category TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'published'
        CHECK (status IN ('published', 'archived')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
INSERT INTO stations VALUES('jan-van-leiden',1,'Der Denker mit dem Strich durch die Rechnung','Ein Täuferkönig – heute sitzt er abseits unter Seligen und Heiligen.',51.96308299999999747,7.626027999999999807,0,'Jan van Leiden an der Kreuzigungsgruppe am St.-Paulus-Dom (Horsteberg)',NULL,'Geschaffen hat die bronzene Kreuzigungsgruppe an der Dom-Nordseite (Horsteberg) der Düsseldorfer Bildhauer Bert Gerresheim – eingesegnet wurde sie am vierten Advent 2004. Ungewöhnlich: Unter dem Kreuz stehen nicht nur Maria und Johannes, sondern auch Gestalten der alten und der neueren Stadtgeschichte – etwa die selige Anna Katharina Emmerick, Schwester Maria Euthymia, Symbole für die heilige Edith Stein, die in Münster lehrte, und Kardinal von Galen. Etwas abseits, auf einem tiefer gelegenen Plateau, thront der Wiedertäuferkönig Jan van Leiden: Eine feine Linie durchschneidet sein Porträt und läuft als „abrechnender Schlussstrich“ über die Pläne auf seinen Knien weiter. Zu seinen Füßen liegen zerbrochene Zeichen – zerschlagene Herrschaftsinsignien und ein Figurentorso als Relikt der Bilderstürmer – und erinnern an dunkle Kapitel der Geschichte. Die Bronze ersetzte eine verwitterte Sandsteingruppe aus dem 18. Jahrhundert.','uploads/2328ae78baffb15fb69fa5d8028190ff.jpg','uploads/6146e5240d2fc742a120b1fbb3999925.jpg','history','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('schleifkerben',2,'Ritzen im Stein – aber wofür?','Der heimische Baumberger Sandstein ist rau und schleift gut.',51.96359482684700026,7.62785837116369958,1,'Schleifkerben am Sandstein-Portal','Lösungsfoto: die Säulen mit Schleifkerben am Portal','Was hier wie zufällige Schrammen aussieht, sind sogenannte Schleifkerben: Der heimische Baumberger Sandstein ist so rau, dass er sich hervorragend zum Schärfen eignet. Über Jahrhunderte hinweg griffen deshalb Händler, Wachen und Marktbesucher zu Messern, Sicheln oder Schwertern und zogen die Klingen an den Steinpfeilern entlang. Jede einzelne Rille ist so das stumme Zeugnis unzähliger Handgriffe aus dem Alltag rund um den Dom. Übrigens: Der Kalksandstein aus den Baumbergen bei Havixbeck prägt weite Teile der Münsteraner Baukunst – auch der St.-Paulus-Dom selbst ist daraus errichtet.','uploads/5437f07eb4b7cd9dbf5ca25f92c85d12.jpg','uploads/a112a5b3613bbf7c3571b75194ecc2a7.jpg','detail','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('kranich-am-dom',3,'Ein Vogel mit Stein in der Kralle','Die Bedeutung geht auf eine Vorstellung in der Antike zurück.',51.96271300000000081,7.625397000000000424,2,'Der Kranich am Dom','Lösungsfoto: „Lösung 3.jpg“ – der Dom von Westen','Hoch oben in der Ostwand des Dom-Querhauses stecken zwei runde Tierreliefs: ein sitzender Hund mit offenem Maul und ein Kranich, der das rechte Bein anhebt und in den Krallen einen ovalen Stein trägt. Um 1900 nahm man die Originale mit ihren Grundplatten aus der Wand und setzte Kopien an ihre Stelle, um sie vor weiterer Verwitterung zu bewahren – die echten Reliefs liegen heute in der Domkammer. Der Kranich mit seinem Stein greift eine Vorstellung aus der Antike auf: Auf dem Zug soll immer einer der Vögel Wache halten und dabei einen Stein in der Kralle tragen. Droht er einzunicken, fällt der Stein klirrend herab und weckt ihn – so bleibt der Wächter wach und aufmerksam. Deshalb gilt der Kranich bis heute als Sinnbild für Wachsamkeit und Klugheit.','uploads/f2d38f916a8ab5cad7bee17d124633da.jpg','uploads/b4091b3ee3b7c39df46fb75f6032c5bf.jpg','art','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('haus-der-niederlande',4,'Ein lateinischer Spruch über der Tür','In diesem Haus forscht man heute über die Nachbarn im Westen.',51.96306374561599739,7.629782830690199801,2,'Haus der Niederlande (Krameramtshaus)','Lösungsfoto: „Lösung 4.jpg“ – das Krameramtshaus','Über dem Eingang des Krameramtshauses begrüßt ein Türsturzrelief die Besucher mit dem lateinischen Spruch „Habent sua fata libelli“ – „Bücher haben ihr Schicksal“. Geschaffen wurde es von Hilde Schürk-Frisch; mit dem vielsagenden Hinweis spielt es auf die wechselvolle Zweckbestimmung des Hauses an. Seit 1588 ist der Bau am Alter Steinweg das Haus der Kramer-Gilde. Während des Friedenskongresses wohnte hier die Delegation der Niederlande – heute beherbergt er das Haus der Niederlande: Einrichtungen, die sich in Forschung, Lehre und wissenschaftlicher Dienstleistung den Niederlanden und Flandern widmen. Gekrönt wird der geschichtsträchtige Giebel vom münsterischen Stadtwappen.','uploads/ddaa0346579215adafe89f47c938e983.jpg','uploads/98fde109a9a12ee4782c3b9044e1f3ca.jpg','detail','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('aegidii-inschriftenstein',5,'Ein Stein voller Geschichte','Ein Nonnenkloster stand hier einst.',51.96051001241099954,7.622080410804500162,2,'Ägidii-Inschriftenstein','Lösungsfoto: „Lösung 5.jpg“ – der Ägidii-Inschriftenstein','Der zwei Meter hohe Stein wurde von Rudolf Breilmann aus Roxel gestaltet und am 7.10.1964 errichtet. Erinnert werden soll mit ihm an das Zisterzienserkloster St. Ägidii – das erste Zisterzienserkloster im Bistum Münster, gegründet um 1180. Drei Seiten tragen Inschriften: Vorn liest man „Hier begrub die Sankt Ägidii-Pfarre 600 Jahre ihre Toten“, links „Hier stand das Zisterzienser Nonnenkloster Sankt Ägidii von 1181 bis 1811“, rechts „Hier stürzte 1821 die Sankt Ägidii Klosterpfarrkirche ein.“','uploads/8648223d375aa062079c408356e8b2f6.jpg','uploads/83fcd5b25b9735b52094602fd668cd4f.jpg','history','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('bispingkreuz-nepomuk',6,'Ein Stein mit düsterer Sage','Ein Heiliger wacht über den Ort einer düsteren Sage.',51.9612334200780026,7.622024255106899738,0,'Bispingkreuz mit Nepomuk-Statue (Johannesbrücke/Bispinghof)','Lösungsfoto: „Lösung 6.jpg“ – Bispingkreuz mit Nepomuk-Statue','An der Stelle, die das Bispingkreuz kennzeichnet, soll einer alten Sage nach ein Professor von seinen Studenten erschlagen worden sein. Vermutlich war das Kreuz ursprünglich ein Grenzstein, der das Gebiet des Bispinghofes absteckte – ein Gut, das dem Bistum unterstand. Den oberen Abschluss bildet eine Nepomuk-Statue von Rudolf Breilmann; sie wurde 1993 anlässlich des 1200-jährigen Stadtjubiläums aufgestellt. (Ort: Johannesbrücke/Bispinghof)','uploads/e8b506835e60a6ce978ce855778bceb2.jpg','uploads/0b3b9ec6dabb81f7ba6373c34b199698.jpg','legend','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('lortzingsaal',7,'Ein Saal mit einem berühmten Namen','Benannt nach einem Komponisten aus Münster.',51.96153721426900062,7.633049682804900372,2,'Lortzingsaal (Café Grotemeyer, heute Restaurant und Cocktailbar)','Lösungsfoto: „Lösung 7.jpg“ – der Lortzingsaal','Albert Lortzing gab dem Saal seinen Namen: Der berühmte Komponist („Zar und Zimmermann“) war von 1826 bis 1833 als Schauspieler und Sänger in Münster tätig – verschiedene Spielstätten und Räume der Stadt wurden nach ihm benannt. Einen besonderen Platz in der Kunstgeschichte nimmt der Saal als Geburtsstätte der „Schanze“ ein: 1919 gründeten dort junge Künstler im Lortzingsaal des Café Grotemeyer die Freie Künstlergemeinschaft Schanze, der es zu verdanken ist, dass moderne Kunst in Münster erstmals regelmäßig gezeigt und diskutiert wurde. Nach einer wechselvollen Geschichte und den Zerstörungen des Zweiten Weltkriegs ist von den alten Kulturstätten kaum mehr als Erinnerung geblieben. Heute beherbergen die Räume des historischen Lortzingsaals ein Restaurant mit Cocktailbar. Der Schriftzug „Lortzingsaal“ ist vor Ort leider nicht mehr zu finden. Der Schriftzug auf diesem Bild ist nachträglich nur zur Anschauung durch KI eingefügt.','uploads/88e193900433d3b678e1eae3341a554c.jpg','uploads/0bd56acb6146702d6107cb3d7bfd7278.jpg','culture','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('jakobsmuschel-lamberti',8,'Ein Zeichen für fernere Ziele','Pilger trugen dieses Zeichen einst am Hut.',51.96301705501200275,7.628925826496899987,1,'Jakobsmuschel (Pilgerzeichen) an St. Lamberti','Lösungsfoto: „Loesung 8.jpg“ – Jakobsmuschel an St. Lamberti','Als Erkennungszeichen der Jakobspilger gilt die Muschel – getragen von allen, die das Grab des Heiligen Jakobus in Santiago de Compostela in Galizien aufsuchen. Eine Legende berichtet, der Apostel Jakobus habe auch in Spanien gepredigt; nach seinem Tod in Jerusalem hätten seine Jünger den Leichnam dorthin überführt. Seit dem Mittelalter ziehen Pilger aus ganz Europa nach Santiago – der Ort zählt neben Rom und Jerusalem zu den beliebtesten Wallfahrtszielen. Vor Ort erwerben die Pilger Muscheln als Beweis für die vollzogene Reise und befestigten sie meist an ihrem Hut. Im Jahr 2000 brachte die Fahrgemeinde Lamberti die Muschel an der Kirche an – als Erinnerung an eine Wallfahrt und als Hinweis darauf, dass Münster schon im Mittelalter an den Pilgerrouten lag.','uploads/e76a9da1c80efd4c72b17acc16c1a2c4.jpg','uploads/ef96c4d7f296dcae859f8d2235cad74a.jpg','faith','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('kinderhand-ludgerikirche',9,'Eine bedeutungsvolle Hand','Die Sage mahnt: Wer die Mutter schlägt, findet keine Ruhe.',51.95825768176199943,7.626999744181399698,1,'Die Kinderhand an der Ludgerikirche','Lösungsfoto: „Loesung 9.jpg“ – die Kinderhand an der Ludgerikirche','Unterhalb eines Chorfensters ist hier eine Kinderhand in Sandstein gemeißelt – inzwischen stark verwittert. Die Sage dahinter: Ein widerspenstiges Kind soll seine Mutter geschlagen haben, starb und wurde auf dem Friedhof bei der Ludgerikirche bestattet. Wenig später, so heißt es, wuchs seine rechte Hand aus dem Grab hervor. So oft man den Hügel auch mit neuer Erde überdeckte – die Hand, die die Mutter geschlagen hatte, tauchte immer wieder auf. Erst ein Gelübde brach den Bann: Die Hand sollte zur Warnung an der Kirchenmauer in Stein verewigt werden. Und siehe da – kaum war der Bildhauer mit seiner Arbeit fertig, war die tote Hand aus dem Grab verschwunden.','uploads/9599ae8db5b8cd0487b0928f60d1225d.jpg','uploads/1ba26bff23d6d87b6ff54ade4b813924.jpg','legend','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('nepomuk-denkmal',10,'Ein echter Heiliger','Er starb, weil er ein Geheimnis nicht verriet.',51.95684574120699751,7.619120811640799573,2,'Johannes-Nepomuk-Denkmal','Lösungsfoto: „Loesung 10.jpg“ – das Johannes-Nepomuk-Denkmal','Die berühmteste Erzählung über den Heiligen Johannes Nepomuk (ca. 1345–1393): Weil er das Beichtgeheimnis bewahrte, soll der Beichtvater der Königin von Böhmen mit dem Leben bezahlt haben. Grund war der eifersüchtige König Wenzel IV., der seine Gemahlin (Sophia bzw. Johanna) der Untreue verdächtigte. Mit Todesdrohung verlangte er von Nepomuk zu erfahren, was ihm die Königin gebeichtet hatte – doch Nepomuk schwieg beharrlich. Daraufhin ließ der König ihn foltern und in der Nacht des 20. März 1393 von der Prager Karlsbrücke in die Moldau werfen, wo er ertrank. Was die Forschung heute ergänzt: Diese Legende entstand erst Jahrzehnte nach Nepomuks Tod. In Wahrheit war seine Hinrichtung Folge eines harten kirchenpolitischen Ringens zwischen Wenzel IV. und dem Prager Erzbischof Johann von Jenstein. Als Generalvikar des Erzbischofs durchkreuzte Nepomuk die Pläne des Königs, der Einfluss der Diözese sollte durch ein neues Bistum geschmälert werden. Zudem bestätigte Nepomuk in einem Eilverfahren die Wahl eines Abtes, der nicht dem Wunschkandidaten des Königs entsprach. Den Preis dafür zahlte er stellvertretend für den geflohenen Erzbischof: festgenommen, gefoltert, getötet. (Quellen: Erzbistum Köln; Ökumenisches Heiligenlexikon)','uploads/3af3bb43709c8c1ef73c9b578bcf1948.jpg','uploads/f4ea9115f31ef3e955681323093320b9.jpg','faith','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('friedensreiter',11,'Ein Reiter, nicht nur für Münster wichtig','Er ritt 1648 zwischen zwei Städten – und brachte endlich Frieden.',51.96261039991900077,7.629332211641299822,1,'Der Friedensreiter am Haus Salzstraße 2','Lösungsfoto: „Loesung 11.jpg“ – der Friedensreiter am Haus Salzstraße 2','Am Haus Salzstraße 2 ist der Friedensreiter zu sehen – während der fünfjährigen Friedensverhandlungen wurde er zum allgemein bekannten Sinnbild des Westfälischen Friedens. Als Postkuriere trugen die Reiter Botschaften und Vertragsentwürfe zwischen den beiden Verhandlungsorten Münster und Osnabrück hin und her – und wurden so zu Garanten dafür, dass der Dreißigjährige Krieg ein Ende fand. Als 1648 die Friedensverträge geschlossen waren, verbreiteten sie selbst die frohe Nachricht: „Jo, gantz Münster, Osnabrugg und alle Welt ist froh“. Zum Jubiläum „350 Jahre Westfälischer Friede“ baute man die alten Wege der Kuriere zu einer 115 Kilometer langen Friedensroute aus, die heute Wanderer, Reiter und Radfahrer anzieht. Stationen der Strecke: Greven, Ladbergen, Tecklenburg, Lengerich, Lienen, Bad Iburg, Hagen und Hasbergen – das Ziel ist das Historische Rathaus von Osnabrück.','uploads/fde2dda1267ab0c60357a6b228dbe7d6.jpg','uploads/662f201569b8d8c5d17f93b86951cf67.jpg','history','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('der-lesende',12,'Ein kleines Männchen lässt Münster schmunzeln','1995 tauchte er einfach auf – und niemand wusste, von wem.',51.96127365853300262,7.627686142083500264,2,'Der Lesende vor der Geschäftsstelle der Westfälischen Nachrichten','Lösungsfoto: „Loesung 12.jpg“ – der Lesende am Prinzipalmarkt','1995 tauchte unter dem Bogen vor der Geschäftsstelle der Westfälischen Nachrichten am Prinzipalmarkt plötzlich ein Lesender auf – einfach so, lustig und witzig. Woher er kam und wozu er gut war, rätselten viele Münsteraner; das Männchen brachte sie zum Nachdenken. Von den Künstlern war zunächst keine Spur. Erst nach einem Aufruf in den Westfälischen Nachrichten vom 11.3.1995 meldeten sich die Schöpfer: die damals 22-jährigen Metallbauer-Azubis Hannes Rademacher und Jan Totzek, denen die Idee in einem kreativen Anflug bei Langeweile in der Berufsschule gekommen war. Ihr Ziel: die Blicke der Münsteraner auf die kleinen, hübschen und amüsanten Details ihres Stadtbilds lenken. Nach konspirativen Vorbereitungen montierten sie das Männeken nachts an seinen Platz.','uploads/6f2656a64701a70a593c84286680b81f.jpg','uploads/061226515b197ba6e7626bac49dfa7ae.jpg','art','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('bierkrieg',13,'Münster im Streik','Wer durstig war, sollte hier 1895 nach 23 Uhr lieber schweigen.',51.96168303078499661,7.628368198149999558,1,'Der Prinzipalmarkt – Schauplatz des Münsterschen Bierkriegs','Lösungsfoto: „Loesung 13.jpg“ – der Prinzipalmarkt, Ort des Bierkriegs','Ende des 19. Jahrhunderts wehrten sich Münsters Wirtshausbesucher mit einer ungewöhnlichen Protestaktion gegen die Vorverlegung der Sperrstunde – der „Münstersche Bierkrieg“. 1895 wollten die preußisch-protestantischen Ordnungsbehörden die bereits seit 1885 angedrohte Schließung der Wirtshäuser um 23 Uhr endlich durchsetzen. Die münsterische Bevölkerung empfand das als Angriff auf ihre individuelle Freiheit. Und so kam es, was kommen musste: Trotz angedrohter Geld- und Gefängnisstrafen versammelten sich in den ersten sieben Oktobernächten die Wirtshausbesucher nach 23 Uhr auf dem Prinzipalmarkt, tranken Bier und sangen Lieder. Zahlreiche Zecher mussten dabei im sogenannten „Höffken“ übernachten – einer Arrestzelle hinter dem Rathaus. Nach sieben Nächten gab der Oberbürgermeister Karl Windthorst nach: Die neue Sperrstunde wurde nicht mehr durchgesetzt.','uploads/d199cad66ab3ee15948e1b7ffd21ee9f.jpg','uploads/666836a1800035fe1d1f6b1103156ff9.jpg','history','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
INSERT INTO stations VALUES('elefant-club',14,'Wo der Panther seinen ersten Auftritt hatte','In diesem Club durften zwei junge Musiker nur unter Aufsicht auf die Bühne.',51.96365664312700261,7.628319867460000125,1,'Der Elefant (Club) am Roggenmarkt 15','Lösungsfoto: „Loesung 14.jpg“ – der Elefant (Club) am Roggenmarkt','„Der Elefant“ war eine feste Größe im münsterschen Nachtleben, bis der Club im Februar 2019 für immer schloss. Erzählt wird aus dieser Zeit eine charmante Anekdote aus den Anfängen zweier späterer Stars: Udo Lindenberg und Steffi Stephan standen hier in den 1960er-Jahren als junge Musiker auf der Bühne. Die war für die beiden streng genommen noch verboten – das Jugendamt mahnte sie wegen ihres Alters ab. Die Lösung des Problems war Steffis Mutter: Sie setzte sich neben die Bühne und strickte. Damit galt die nötige Aufsicht als geleistet, der Auftritt konnte stattfinden. Bis heute erinnert das Schild mit dem Elefanten-Logo an diesen legendären Club und seine Geschichten.','uploads/cf5114c0804e3d35a2e24be76ca81c0d.jpg','uploads/a89b569a37d24a09d5b5e641a20501f7.jpg','culture','published','2026-10-15T18:02:37+02:00','2026-10-15T18:02:37+02:00');
ALTER TABLE stations ADD COLUMN teaser_text TEXT;
CREATE TABLE options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    station_id TEXT NOT NULL,
    option_index INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    UNIQUE (station_id, option_index)
);
INSERT INTO options VALUES(1,'jan-van-leiden',0,'Ein Anführer der Täuferbewegung, der im 16. Jahrhundert die Stadt Münster prägte.');
INSERT INTO options VALUES(2,'jan-van-leiden',1,'Johann von Hoya, ein Fürstbischof von Münster, der im 16. Jahrhundert die Geschicke des Hochstifts lenkte.');
INSERT INTO options VALUES(3,'jan-van-leiden',2,'Johann von der Recke, ein bedeutender Domherr, der sich für kirchliche Belange in Münster einsetzte.');
INSERT INTO options VALUES(4,'schleifkerben',0,'Als Maßmarkierungen für den mittelalterlichen Handel.');
INSERT INTO options VALUES(5,'schleifkerben',1,'Zum Schärfen von Messern durch Marktständler.');
INSERT INTO options VALUES(6,'schleifkerben',2,'Zum Befestigen von Marktständen an der Mauer.');
INSERT INTO options VALUES(7,'kranich-am-dom',0,'Ein Greifvogel als Sinnbild für Macht und Stärke.');
INSERT INTO options VALUES(8,'kranich-am-dom',1,'Ein Pelikan als christliches Symbol für Aufopferung und Nächstenliebe.');
INSERT INTO options VALUES(9,'kranich-am-dom',2,'Ein Kranich als Sinnbild für Wachsamkeit und Klugheit.');
INSERT INTO options VALUES(10,'haus-der-niederlande',0,'Bücher bewahren das Wissen ihrer Zeit.');
INSERT INTO options VALUES(11,'haus-der-niederlande',1,'Bücher erhalten ihre Bedeutung erst durch ihre Leser.');
INSERT INTO options VALUES(12,'haus-der-niederlande',2,'Bücher haben ihr Schicksal.');
INSERT INTO options VALUES(13,'aegidii-inschriftenstein',0,'Erstes Franziskanerkloster im Bistum Münster');
INSERT INTO options VALUES(14,'aegidii-inschriftenstein',1,'Zweites Zisterzienserkloster im Bistum Münster');
INSERT INTO options VALUES(15,'aegidii-inschriftenstein',2,'Erstes Zisterzienserkloster im Bistum Münster');
INSERT INTO options VALUES(16,'bispingkreuz-nepomuk',0,'Studenten erschlugen hier einen Professor');
INSERT INTO options VALUES(17,'bispingkreuz-nepomuk',1,'Studenten erschlugen hier einen Bürgermeister');
INSERT INTO options VALUES(18,'bispingkreuz-nepomuk',2,'Der Professor erschlug hier einen Studenten');
INSERT INTO options VALUES(19,'lortzingsaal',0,'Albert Lortzing: Operettenkomponist, bekannt für humorvolle Bühnenwerke.');
INSERT INTO options VALUES(20,'lortzingsaal',1,'Albert Lortzing: Deutscher Musikpädagoge und Kirchenmusiker.');
INSERT INTO options VALUES(21,'lortzingsaal',2,'Albert Lortzing: Schauspieler, Sänger und Komponist.');
INSERT INTO options VALUES(22,'jakobsmuschel-lamberti',0,'Eine Gedenktafel der Kaufmannsgilde am Markt.');
INSERT INTO options VALUES(23,'jakobsmuschel-lamberti',1,'Ein Zeichen für den Jakobsweg, der an St. Lamberti vorbeiführt.');
INSERT INTO options VALUES(24,'jakobsmuschel-lamberti',2,'Das Symbol der Kaufmannsgilde für ihre Handelswege.');
INSERT INTO options VALUES(25,'kinderhand-ludgerikirche',0,'Die Hand eines Baumeisters als Signatur des Gotteshauses.');
INSERT INTO options VALUES(26,'kinderhand-ludgerikirche',1,'Die Hand eines Kindes, das seine Mutter schlug und keine Ruhe fand.');
INSERT INTO options VALUES(27,'kinderhand-ludgerikirche',2,'Eine Segenshand, die Gläubige willkommen heißt.');
INSERT INTO options VALUES(28,'nepomuk-denkmal',0,'Er starb, weil er den König beleidigte.');
INSERT INTO options VALUES(29,'nepomuk-denkmal',1,'Er starb, weil er seine Heimat verraten hatte.');
INSERT INTO options VALUES(30,'nepomuk-denkmal',2,'Er starb, weil er ein Geheimnis nicht verriet.');
INSERT INTO options VALUES(31,'friedensreiter',0,'Ein Denkmal für die siegreichen Reitergeneräle des Krieges.');
INSERT INTO options VALUES(32,'friedensreiter',1,'Ein Reiter, der als Bote zwischen zwei Städten unterwegs war.');
INSERT INTO options VALUES(33,'friedensreiter',2,'Ein Wahrzeichen für den Handel mit Salz auf dieser Straße.');
INSERT INTO options VALUES(34,'der-lesende',0,'Ein Denkmal für die Pressefreiheit, gestiftet vom Verlag.');
INSERT INTO options VALUES(35,'der-lesende',1,'Ein Kunstwerk junger Handwerker, das hier ganz offiziell angekauft wurde.');
INSERT INTO options VALUES(36,'der-lesende',2,'Ein Kunstwerk, das heimlich über Nacht an seinem Platz erschien.');
INSERT INTO options VALUES(37,'bierkrieg',0,'Die Bürger protestierten gegen eine Bier-Steuer.');
INSERT INTO options VALUES(38,'bierkrieg',1,'Die Bürger wehrten sich gegen eine frühere Sperrstunde.');
INSERT INTO options VALUES(39,'bierkrieg',2,'Die Wirte streikten gegen zu hohe Pacht.');
INSERT INTO options VALUES(40,'elefant-club',0,'Die Musiker durften nur mittags auftreten, wenn keine Gäste im Club waren.');
INSERT INTO options VALUES(41,'elefant-club',1,'Eine strickende Mutter neben der Bühne erfüllte die Auflagen des Jugendamts.');
INSERT INTO options VALUES(42,'elefant-club',2,'Ein Vater der Band tauchte als Aufpasser auf und sammelte die Eintritte.');
CREATE TABLE links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    station_id TEXT NOT NULL,
    title TEXT NOT NULL,
    url TEXT NOT NULL,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
);
INSERT INTO links VALUES(1,'jan-van-leiden','Paulusdom: Kreuzigungsgruppe am Dom','https://www.paulusdom.de/gotteshaus/kunstwerke/kunstwerke-des-st-paulus-domes/kreuzigungsgruppe-am-dom');
INSERT INTO links VALUES(2,'jan-van-leiden','Stadt Münster: Kreuzmonument Horsteberg','https://www.stadt-muenster.de/kriegerdenkmale/erinnern-nach-2000/kreuzmonument-horsteberg');
INSERT INTO links VALUES(3,'jan-van-leiden','Wikipedia: Bert Gerresheim','https://de.wikipedia.org/wiki/Bert_Gerresheim');
INSERT INTO links VALUES(4,'schleifkerben','Wikipedia: Baumberger Kalksandstein','https://de.wikipedia.org/wiki/Baumberger_Kalksandstein');
INSERT INTO links VALUES(5,'schleifkerben','Baumberger Sandsteinmuseum (Havixbeck)','https://de.wikipedia.org/wiki/Baumberger_Sandsteinmuseum');
INSERT INTO links VALUES(6,'kranich-am-dom','Museen in Münster: Domkammer','https://www.museen-in-muenster.de/details/de_de-domkammer-des-st.-paulus-domes-muenster');
INSERT INTO links VALUES(7,'haus-der-niederlande','Haus der Niederlande: Wandlungen eines Baudenkmals','https://www.uni-muenster.de/HausDerNiederlande/ueber/baudenkmal.shtml');
INSERT INTO links VALUES(8,'haus-der-niederlande','Wikipedia: Haus der Niederlande','https://de.wikipedia.org/wiki/Haus_der_Niederlande');
CREATE TABLE admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL
);
INSERT INTO admins VALUES(1,'stina','$2y$10$cHl0bcgYbmx1NDg.tnuFj.XoSzHxuY9usLgd5Lv1JPAdiaghLUu9a',1,'2026-10-15T18:02:37+02:00');
INSERT INTO admins VALUES(2,'kolja','$2y$10$/pMw9c3GpmrjyilSJWIcie.uFvjcje2lXe30WUuscInO1fFdTH8YC',1,'2026-10-15T18:02:37+02:00');
INSERT INTO admins VALUES(3,'admin','$2y$10$IGkihAXgIXOn0plg9aPgqulu4B/F1nHHY93asiJHIUMW3vDrA9YT6',1,'2026-10-15T18:02:37+02:00');
CREATE TABLE station_suggestions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    hint TEXT,
    lat NUMERIC,
    lon NUMERIC,
    question TEXT,
    options_json TEXT,
    correct_option_index INTEGER,
    info TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'detail',
    suggested_by_name TEXT,
    suggested_by_email TEXT,
    task_image TEXT,
    solution_image TEXT,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'approved', 'rejected')),
    created_at TEXT NOT NULL,
    reviewed_at TEXT
);
INSERT INTO station_suggestions VALUES(1,'Rätsel am Prinzipalmarkt','Finde das besondere Detail an den Giebeln.',51.96249999999999858,7.625700000000000144,'Welche Farbe hat das auffällige Element?','["Rot","Blau","Gold"]',2,'Ein exemplarischer Vorschlag für eine Station am Prinzipalmarkt.','detail','Mustername 1','beispiel1@example.invalid','uploads/58e8c99a35d49d0a4d69a6374ae6e574.jpg',NULL,'pending','2026-10-15T18:02:37+02:00',NULL);
INSERT INTO station_suggestions VALUES(2,'Spuren an der Promenade','Welche Geschichte verbirgt sich hier?',51.96509999999999963,7.628800000000000025,'Was ist auf dem Bild zu sehen?','["Ein Tor","Eine Brücke","Ein Turm"]',1,'Ein exemplarischer Vorschlag mit einem vorhandenen Beispielbild.','detail','Mustername 2','beispiel2@example.invalid','uploads/07fc5968147129b9f15a963b52034e90.jpg',NULL,'pending','2026-10-15T18:02:37+02:00',NULL);
INSERT INTO station_suggestions VALUES(3,'Kleines Detail am Dom','Ein genaues Hinsehen lohnt sich.',51.96209999999999952,7.625200000000000422,'Wie viele Symbole sind zu erkennen?','["Zwei","Drei","Vier"]',0,'Ein exemplarischer offener Vorschlag für die Moderation.','detail','Mustername 3','beispiel3@example.invalid','uploads/acc4d66dd6bf653d7b71add7c5a8f791.jpg',NULL,'pending','2026-10-15T18:02:37+02:00',NULL);
CREATE TABLE rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bucket TEXT NOT NULL,
    key TEXT NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE categories (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    icon TEXT,
    color TEXT NOT NULL DEFAULT '#065f46'
        CHECK (length(color) = 7 AND color GLOB '#[0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f][0-9A-Fa-f]'),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
INSERT INTO categories VALUES('history','Geschichte','Historische Ereignisse und Orte.',NULL,'#92400e','2026-10-15 16:02:37','2026-10-15 16:02:37');
INSERT INTO categories VALUES('art','Kunst','Kunstwerke und Skulpturen im Stadtbild.',NULL,'#9d174d','2026-10-15 16:02:37','2026-10-15 16:02:37');
INSERT INTO categories VALUES('legend','Sagen','Sagen und Legenden aus Münster.',NULL,'#5b21b6','2026-10-15 16:02:37','2026-10-15 16:02:37');
INSERT INTO categories VALUES('faith','Glaube','Kirchen und religiöse Orte.',NULL,'#1d4ed8','2026-10-15 16:02:37','2026-10-15 16:02:37');
INSERT INTO categories VALUES('detail','Details','Kleine Besonderheiten am Wegesrand.',NULL,'#065f46','2026-10-15 16:02:37','2026-10-15 16:02:37');
INSERT INTO categories VALUES('culture','Kultur','Kultur, Brauchtum und Kuriositäten.',NULL,'#b45309','2026-10-15 16:02:37','2026-10-15 16:02:37');
INSERT INTO settings VALUES('ui.location_gate','on','2026-10-19 17:44:00');
INSERT INTO sqlite_sequence VALUES('admins',3);
INSERT INTO sqlite_sequence VALUES('options',42);
INSERT INTO sqlite_sequence VALUES('links',8);
INSERT INTO sqlite_sequence VALUES('station_suggestions',3);
CREATE UNIQUE INDEX stations_number_unique ON stations(number);
CREATE INDEX stations_status_index ON stations(status);
CREATE INDEX suggestions_status_index ON station_suggestions(status);
CREATE INDEX rate_limits_lookup ON rate_limits(bucket, key, created_at);
COMMIT;
