=== Akismet Privacy Policies ===
Contributors: inpsyde, Bueltge
Tags: akismet, privacy, spam
Requires at least: 3.0
Tested up to: 4.9
Stable tag: 1.1.2

Ergänzt das Kommentarformular um datenschutzrechtliche Hinweise bei Nutzung des Plugins Akismet.

== Description ==
Der Einsatz des Anti-Spam-Plugins Akismet ist in Deutschland aus datenschutzrechtlichen Aspekten sehr bedenklich, da personenbezogene Daten auf Servern in den USA gespeichert werden.

Um keine Angriffsfläche für Abmahnungen zu bieten, muss man die Benutzer vor dem Kommentieren auf das Speichern dieser Daten hinweisen. Dies übernimmt das Plugin.

= Erstellt durch Inpsyde =
Das Team der [Inpsyde](https://inpsyde.com) entwickelt im Web und WordPress seit 2006.

= Spenden? =
Du möchtest etwas spenden - wir bevorzugen ein positives Review, nicht mehr.

== Installation ==
1. Plugin herunterladen, entpacken, in den Ordner `wp-content/plugins/` laden und aktivieren. Oder direkt über den Adminbereich und 'Plugins' - 'Installieren' das Plugin suchen und installieren.
2. Das Plugin sollte nun automatisch unter dem Kommentarfeld den Hinweistext anzeigen. Falls nicht, muss im Theme (z.B. comments.php) manuell folgender Code innerhalb des Kommentar-Formulares, innerhalb `<form>...</form>` - da wo der Hinweis erscheinen soll, eingefügt werden:

    `<?php do_action( 'akismet_privacy_policies' ); ?>`

Der Aufruf muss an der Stelle des Templates statt finden, wo die Ausgabe erscheinen soll.

== Frequently Asked Questions ==
= Wo finde ich weitere Informationen zum Thema Datenschutz und Akismet? =

Wir haben bei WordPress Deutschland einen [FAQ-Artikel](http://http://faq.wpde.org/hinweise-zum-datenschutz-beim-einsatz-von-akismet-in-deutschland/ "FAQ-Artikel zu Akismet").

Rechtsanwalt Thomas Schwenke klärt in einem Artikel auf: [Usability VS Datenschutz – Datenschutzrechtliche Einwilligung ohne Opt-In?](http://spreerecht.de/datenschutz/2011-04/usability-vs-datenschutz-datenschutzrechtliche-einwilligung-ohne-opt-in)

= Plugin Dokumentation =
* [Hook-Documentation](https://github.com/inpsyde/Akismet-Privacy-Policies/wiki/Hook-Documentation)

== Screenshots ==
1. So sieht das Plugin im Einsatz aus.
2. Die optionalen Einstellungen im Backend von WordPress

== Changelog ==
= 1.1.2 =
* Link zum Datenschutzhintergrund ergänzt
* Source-Codex Anpassungen

= 1.1.1 =
* Prüfung auf Sprache der WordPress Installation, nur bei `de_DE` als Sprachschlüssel, werden die Hinweise ergänzt

= 1.1.0 =
* Weitere Hinweistexte und Mustertext für Datenschutzerklärung

= 1.0.0 =
* Release first version

