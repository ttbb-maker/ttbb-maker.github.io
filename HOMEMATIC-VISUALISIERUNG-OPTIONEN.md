# Homematic-Visualisierung — Optionsvergleich

**Ausgangslage:** CCU3 / RaspberryMatic mit XML-API-Addon. Bisher Mediola Neo,
soll wegen Lizenzkosten ersetzt werden. Gesucht: eine kostenlose Bedien- und
Anzeige-Oberfläche.

**Wichtig vorab:** Mediola Neo ist im Kern eine *Visualisierung/Fernbedienung* —
die Automatisierungs-Logik (Programme, Verknüpfungen) läuft ohnehin auf der CCU.
Du brauchst also keine neue „Zentrale", sondern eine schöne Bedienoberfläche.
Das vereinfacht die Wahl erheblich.

---

## Die Optionen auf einen Blick

| Kriterium | **A) Selbst bauen** | **B) Home Assistant** | **C) ioBroker + vis-2/Jarvis** |
|---|---|---|---|
| Einrichtungsaufwand | gering–mittel | mittel–hoch | mittel–hoch |
| Laufende Wartung | sehr gering | regelmäßig (Updates) | regelmäßig (Adapter) |
| Lernkurve | keine (fertig geliefert) | spürbar | spürbar (Bastler) |
| Optik mobil | dein eigener Look | sehr gut, native App | sehr gut, anpassbar |
| Push-Benachrichtigungen | nein (ohne Mehraufwand) | ja (App) | ja (Adapter) |
| History / Charts | nur wenn eingebaut | ja | ja |
| Eigene Automatik-Engine | nein (läuft auf CCU) | ja | ja |
| Fremdgeräte (andere Hersteller) | nein | sehr viele | sehr viele |
| Zusätzliches System nötig | nein¹ | ja (Docker) | ja (Docker) |
| Kosten | 0 € | 0 €² | 0 € |
| Passt in deinen Stack | ✅ perfekt | ❌ separat | ❌ separat |

¹ Nutzt deine bestehende Synology (PHP-Proxy) — kein neuer Dienst.
² Optional „Nabu Casa" (~6,50 €/Monat) für komfortablen Fernzugriff/Sprache — nicht nötig.

---

## A) Selbst bauen — HTML-Dashboard + PHP-Proxy

**So funktioniert es:** Eine HTML-Seite auf GitHub Pages (gleicher Look wie
Fahrtenbuch/Aquarium/Finanzen) ruft einen kleinen PHP-Proxy auf deiner Synology
auf. Der Proxy spricht per **XML-API-Addon** mit der CCU — liest Gerätezustände
und schaltet/dimmt/fährt Rollläden/setzt Solltemperaturen und löst CCU-Programme aus.
(Direkt aus dem Browser geht nicht: CCU spricht nur HTTP, dazu CORS-/Mixed-Content-Sperren.)

- ➕ Passt nahtlos in deinen Stack, kein zweites System, keine Lizenz, kaum Wartung.
- ➕ Genau dein Design, als Web-App auf den Home-Screen legbar.
- ➕ Deckt alles ab, was Neo dir an Bedienung bietet: Schalten, Dimmen, Rollläden,
  Thermostat-Sollwerte, Sensorwerte, Szenen/Programme, Systemvariablen.
- ➖ Keine fertige Automatik-Engine (brauchst du nicht — läuft auf der CCU),
  Push und History nur mit Zusatzaufwand.
- **Aufwand für dich:** XML-API-Addon auf der CCU installieren (kostenlos),
  zwei Dateien auf die Synology legen, CCU-IP + Raum-/Geräteliste nennen. Den Rest baue ich.

## B) Home Assistant (Docker auf der Synology)

Vollwertige Smart-Home-Plattform. CCU-Anbindung über die Integration
„Homematic(IP) Local". Geräte werden meist automatisch erkannt.

- ➕ Schönste Dashboards, native Apps mit Push, History/Charts, riesiges Ökosystem,
  Fremdgeräte aller Art, echte Automatisierungen.
- ➖ Ein zweites, dauerhaft laufendes „Gehirn" neben der CCU; regelmäßige Updates
  (gelegentlich Breaking Changes); spürbare Einarbeitung; passt nicht in deinen schlanken Stack.
- **Lohnt sich, wenn** du mehr willst als bedienen: Push, History, Sprachassistenten,
  Geräte anderer Hersteller, komplexe Logik.

## C) ioBroker + vis-2 / Jarvis (Docker auf der Synology)

In der deutschen Homematic-Szene der Klassiker als Neo-Ersatz. CCU-Anbindung über
Adapter (hm-rega/hm-rpc bzw. hmip), Visualisierung mit vis-2 (privat kostenlos) oder Jarvis.

- ➕ Sehr flexibel, Scripting (Blockly/JS), History, viele Adapter.
- ➖ Bastler-Plattform: Visualisierung muss selbst zusammengebaut werden,
  Adapter-/System-Pflege, Lernkurve. Ebenfalls ein separates System.

## Kurz erwähnt
- **CCU-eigene WebUI:** kostenlos, kein Extra-System — aber altbacken und mobil mäßig.
- **openHAB / FHEM:** mächtig und kostenlos, aber höhere Lernkurve / altbackene Optik.

---

## Empfehlung

| Dein Ziel | Beste Wahl |
|---|---|
| „Ich will nur sauber **bedienen + Werte sehen + Szenen auslösen**" (wie Neo) | **A) Selbst bauen** |
| „Ich will eine echte Zentrale mit **Push, History, Fremdgeräten, Automatik**" | **B) Home Assistant** |
| „Ich **bastle gern**, will maximale Flexibilität, Zeit ist da" | **C) ioBroker** |

**Für deinen Fall (Neo nur als Visualisierung, vorhandene Synology+GitHub-Pages-Infrastruktur,
Wunsch nach wenig Wartung) ist A) der naheliegendste Weg** — schlank, kostenlos, im
gewohnten Look und ohne ein weiteres System, das gepflegt werden muss. B) ist der
klare Sieger, falls du den Schritt zur vollen Plattform ohnehin machen willst.
