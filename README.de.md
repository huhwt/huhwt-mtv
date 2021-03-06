ℍ&ℍwt - HuH Extensions for webtrees - Multi-Treeview
============================

[![Latest Release](https://img.shields.io/github/v/release/huhwt/huhwt-mtv)][1]
[![webtrees major version](https://img.shields.io/badge/webtrees-v2.x-green)][2]
[![Downloads](https://img.shields.io/github/downloads/huhwt/huhwt-mtv/v1.0/total)]()

Erweiterungen für Webtrees zur Prüfung und Anzeige von Duplikaten und anderen Inkonsistenzen in der Datenbank.

Einführung
-----------

Wenn man ein paar Jahre in einem der großen Genealogie-Dienste gearbeitet hat, ist die Chance groß, dass Sie durch die Nutzung von Abgleichsdiensten auch Duplikate erhalten, da die Qualität der Daten manchmal recht fragwürdig ist.

Dann ist es praktisch eine Funktion zu haben, um die Daten mit visuellen Hilfsmitteln nicht nur für jeden Abgleich einzeln, sondern auf einem Bildschirm gleichzeitig zu prüfen.

Die Abgleichliste in der 'Duplikate'-Ansicht für 'Personen' wird um einen Eintrag 'Interaktiver Vergleich' erweitert, der ein 'Interaktives Sanduhr-Diagramm' für jedes Individuum zusammen auf dem Bildschirm anzeigt. Dies geschieht auch wenn mehr als 2 Personen von der Funktion erfasst werden. 

Installation und Upgrading
--------------------------

... auf die übliche Art und Weise: Laden Sie die Zip-Datei herunter, entpacken Sie sie in das modules_v4-Verzeichnis, und das war's. Man sollte die vorhandene Version vorher komplett entfernen.

Danksagung
--------------------------

Fürs Test, Anregungen und Kritik besonderen Dank an Hermann Harthentaler.

Danke für die Übersetzung ins Niederländische an TheDutchJewel.

Development
-------------------------

[TODO]

Derzeit gibt es False-Positives weil auch dann gematcht wird, wenn Individuen nur das '_MARNM'-tag teilen. Die Filter-Funktion sollte durch explizite Abfrage des 'NAME'-tags geschärft werden.

Hard-core Workaround: In AdminService.php einfügen nach Zeile 136:

    >            ->where('n_type', '=', 'NAME')          /** EW.H - MOD ... avoid false positives because of tag '_MARNM' */

Bugs and feature requests
-------------------------
If you experience any bugs or have a feature request for this theme you can [create a new issue][3].

[1]: https://github.com/huhwt/huhwt-mtv/releases/latest
[2]: https://webtrees.net/download
[3]: https://github.com/huhwt/huhwt-mtv/issues?state=open