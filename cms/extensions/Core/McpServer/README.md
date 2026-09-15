# Core/McpServer

Kern-Extension für die Anbindung von KI-Assistenten an Frootbox über MCP.

## Stand

Das Grundgerüst registriert eine Backend-App „MCP Server“ und die Route `mcp`
(mit optionalem abschließendem Slash) über den vorhandenen Extension-Installer.
Die App zeigt den Einrichtungsstand. Es gibt noch keine speicherbaren Einstellungen.

Der Endpunkt antwortet für alle HTTP-Methoden mit **503 Service Unavailable**
und `Cache-Control: no-store`. Bei HEAD entfällt der Body. Die JSON-Antwort
`mcp_not_configured` ist eine vorläufige HTTP-Fehlermeldung, keine MCP-Antwort.
Authentifizierung, JSON-RPC/MCP-Verarbeitung und Werkzeuge sind noch nicht
implementiert. Die Extension ist daher noch nicht mit einem MCP-Client nutzbar.

## Installation

Die Extension `Core/McpServer` über die bestehende Extension-Verwaltung
installieren/aktivieren und deren Initialisierung ausführen. Dabei werden App
und Route registriert. Es sind keine neuen Datenbanktabellen erforderlich.
Die Route reserviert `/mcp` unter der CMS-Basis-URL; diese Adresse darf nicht
gleichzeitig für eine Inhaltsseite verwendet werden.

## Vorgesehener Ausbau

- `classes/Apps/McpServer/`: Einrichtung, Verbindungen und später Testoberfläche.
- `classes/Routing/McpRoute.php`: HTTP-Einstieg; später Übergabe an einen separaten
  Transport-/Serverdienst, statt Protokolllogik in der Backend-App abzulegen.
- Authentifizierung als eigener Dienst: Für ChatGPT OAuth einplanen, Benutzer
  und Website serverseitig bestimmen und Rechte bei jedem Werkzeugaufruf prüfen.
  Eine CMS-Backend-Sitzung allein autorisiert keine MCP-Aufrufe.
- Danach MCP-Initialisierung und Werkzeugverwaltung implementieren. Als erster
  Verbindungstest eignet sich ein lesendes Werkzeug `server_info`, das nur
  feste Serverinformationen zurückgibt und keine CMS-Daten verändert.
- Seiten- und Inhaltswerkzeuge anschließend über die vorhandene CMS-Logik
  anbinden. Veröffentlichung erhält eine eigene Berechtigung.

## Manuelle Prüfung nach der Installation

Backend-App „MCP Server“ öffnen. Ein Aufruf von `/mcp` muss HTTP 503 und
`mcp_not_configured` liefern, auch bei POST. `/mcp/` muss dieselbe Route treffen;
`/mcp/anderer-pfad` darf nicht von dieser Route übernommen werden.

Dieses Grundgerüst nimmt keine Änderungen an einer laufenden Installation vor.
