
Diese Datei ist in Plain Old Documentation geschrieben. Mit

> perldoc INSTALL.fcgi

ist sie deutlich leichter zu lesen.

=head1 FastCGI f�r Lx-Office

=head2 Was ist FastCGI?

Direkt aus L<http://de.wikipedia.org/wiki/FastCGI> kopiert:

  FastCGI ist ein Standard f�r die Einbindung externer Software zur Generierung
  dynamischer Webseiten in einem Webserver. FastCGI ist vergleichbar zum Common
  Gateway Interface (CGI), wurde jedoch entwickelt, um dessen
  Performance-Probleme zu umgehen.


=head2 Warum FastCGI?

Perl Programme (wie Lx-Office eines ist) werden nicht statisch kompiliert.
Stattdessen werden die Quelldateien bei jedem Start �bersetzt, was bei kurzen
Laufzeiten einen Gro�teil der Laufzeit ausmacht. W�hrend SQL Ledger einen
Gro�teil der Funktionalit�t in einzelne Module kapselt, um immer nur einen
kleinen Teil laden zu m�ssen, ist die Funktionalit�t von Lx-Office soweit
gewachsen, dass immer mehr Module auf den Rest des Programms zugreifen.
Zus�tzlich benutzen wir umfangreiche Bibliotheken um Funktionalt�t nicht selber
entwickeln zu m�ssen, die zus�tzliche Ladezeit kosten. All dies f�hrt dazu dass
ein Lx-Office Aufruf der Kernmasken mittlerweile deutlich l�nger dauert als
fr�her, und dass davon 90% f�r das Laden der Module verwendet wird.

Mit FastCGI werden nun die Module einmal geladen, und danach wird nur die
eigentliche Programmlogik ausgef�hrt.

=head2 Kombinationen aus Webservern und Plugin.

Folgende Kombinationen sind getestet:

 * Apache 2.2.11 (Ubuntu) und mod_fastcgi.

Folgende Kombinationen funktionieren nicht:

 * Apache 2.2.11 (Ubuntu) + mod_fcgid:



=head2 Konfiguration des Webservers.

Variante 1:

  AddHandler fastcgi-script .pl

Variante 2:

  AliasMatch ^/web/path/to/lx-office-erp/[^/]+\.pl /path/to/lx-office-erp/dispatcher.fpl

  <Directory /path/to/lx-office-erp>
    AllowOverride All
    AddHandler fastcgi-script .fpl
    Options ExecCGI Includes FollowSymlinks
    Order Allow,Deny
    Allow from All
  </Directory>

  <DirectoryMatch //.*/users>
    Order Deny,Allow
    Deny from All
  </DirectoryMatch>


Variante 1 startet einfach jeden Lx-Office Request als fcgi Prozess. F�r sehr
gro�e Installationen ist das die schnellste Version, ben�tigt aber sehr viel
Arbeitspseicher (ca. 2GB).

Variante 2 startet nur einen zentralen Dispatcher und lenkt alle Scripte auf
diesen. Dadurch dass zur Laufzeit �fter mal Scripte neu geladen werden gibt es
hier kleine Performance Einbu�en.


=head2 Entwicklungsaspekte

Die AddHandler Direktive vom Apache ist entgegen der Dokumentation
anscheinend nicht lokal auf das Verzeichnis beschr�nkt sondern global im
vhost.

Wenn �nderungen in der Konfiguration von Lx-Office gemacht werden, oder wenn
Templates editiert werden muss der Server neu gestartet werden.

Es ist m�glich die gleiche Lx-Office Version parallel unter cgi und fastcgi zu
betreiben. Da nimmt man Variante 2 wie oben beschrieben, und �ndert die
MatchAlias Zeile auf eine andere URL, und l�sst alle anderen URLs auch
weiterleiten:


  AliasMatch ^/web/path/to/lx-office-erp-fcgi/[^/]+\.pl /path/to/lx-office-erp/dispatcher.fpl
  AliasMatch ^/web/path/to/lx-office-erp-fcgi/(.*) /path/to/lx-office-erp/$1

Dann ist unter C</web/path/to/lx-office-erp/> die normale Version erreichbar,
und unter C</web/opath/to/lx-office-erp-fcgi/> die FastCGI Version.

Bei der Entwicklung f�r FastCGI ist auf ein paar Fallstricke zu achten. Dadurch
dass das Programm in einer Endlosschleife l�uft, m�ssen folgende Aspekte
geachtet werden:

=head3 C<warn>, C<die>, C<exit>, C<carp>, C<confess>

Fehler die normalerweise dass Programm sofort beenden (fatale Fehler) werden
mit dem FastCGI Dispatcher abgefangen, um das Programm amLaufen zu halten. Man
kann mit C<die>, C<confess> oder C<carp> Fehler ausgeben, die dann vom Dispatcher
angezeigt werden. Die Lx-Office eigene C<$::form->error()> tut im Prinzip das
Gleiche, mit ein paar Extraoptionen. C<warn> und C<exit> hingegen werden nicht
abgefangen. C<warn> wird direkt nach STDERR, also in Server Log eine Nachricht
schreiben, und C<exit> wird die Ausf�hrung beenden.

Prinzipiell ist es ein Beinbruch, wenn sich der Prozess beendet, fcgi wird ihn
sofort neu starten, allerdings sollte das die Ausnahme sein. Quintessenz: Bitte
kein C<warn> oder C<exit> benutzen, alle anderen Excepionmechanismen sind ok.

=head3 Globale Variablen

Um zu vermeiden, dass Informationen von einem Request in einen anderen gelangen
m�ssen alle globalen Variablen vor einem Request sauber initialisiert werden.
Das ist besonders wichtig im C<$::cgi> und C<$::auth> Objekt, weil diese nicht
gel�scht werden pro Instanz, sondern persistent gehalten werden.

Datenbankverbindungen wird noch ein Guide verfasst werden, wie man sichergeht,
dass man die richtige erwischt.

=head2 Performance und Statistiken

Die kritischen Pfade des Programms sind die Belegmasken, und unter diesen ganz
besonders die Verkaufsrechnungsmaske. Ein Aufruf der Rechnungsmaske in
Lx-Office 2.4.3 stable dauert auf einem Core2duo mit 2GB Arbeitsspeicher und
Ubuntu 9.10 eine halbe Sekunde. In der 2.6.0 sind es je nach Menge der
definierten Variablen 1-2s. Ab der Moose/Rose::DB Version sind es 5-6s.

Mit FastCGI ist die neuste Version auf 0,4 Sekunden selbst in den kritischen
Pfaden, unter 0,15 sonst.

=head2 Bekannte Probleme

Bei mehreren Benutzern scheint ab und zu eine Datenbankverbidung von Rose::DB
in den falschen Benutzer zu geraten. Das ist ein kritischer Bug und muss gefixt
werden.

Bei Administrativen T�tigkeiten werden in seltenen F�llen die Locales nicht
richtig geladen und die Maske erscheint in Englisch.
