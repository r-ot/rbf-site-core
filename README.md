# RBF Site Core

Site-spezifische Datenstrukturen, Datenlogik, REST-Endpunkte und Fallback-Registrierungen für das SOMA WooCommerce-Projekt.

## Architektur

Grundsätzliche Trennung:

* **WooCommerce / WordPress** = Source of Truth
* **Plugin** = Datenlogik, Aggregationen, REST und funktionale Komponenten
* **Theme** = Darstellung, CSS, Template-Overrides und projektspezifisches Markup

REST-Endpunkte liefern grundsätzlich strukturierte JSON-Daten und kein fertiges HTML.

Businesslogik soll unabhängig von REST und Theme wiederverwendbar bleiben.

---

## Interne Daten-Aliases

Externe WooCommerce-/ERP-Keys sollen möglichst nicht direkt im restlichen Projekt verwendet werden.

Dafür existiert:

```text
RbfSiteCoreDataKeys
```

Verbindliche interne Aliases:

```text
tire_dimension
chain_strength
```

Aktuelles Mapping der Testdaten:

```text
tire_dimension
→ tire_dimension_fit

chain_strength
→ gliederstaerke
```

Die Product-Family-Taxonomie wird ebenfalls über das zentrale Key-Mapping aufgelöst.

Aktuell:

```text
product_family
→ product_family
```

Wenn das ERP später andere finale Keys liefert, sollen möglichst nur die zentralen Mappings geändert werden.

Die internen Aliases:

```text
tire_dimension
chain_strength
```

bleiben stabil.

---

## Plugin-Struktur

```text
rbf-site-core/
├── README.md
├── rbf-site-core.php
├── assets/
│   └── js/
│       └── product-families.js
├── inc/
│   ├── class-rbf-site-core.php
│   ├── class-rbf-site-core-rest.php
│   ├── class-rbf-site-core-shortcodes.php
│   ├── data/
│   │   ├── class-rbf-site-core-data-keys.php
│   │   ├── class-rbf-site-core-products.php
│   │   └── class-rbf-site-core-family-index.php
│   └── admin/
│       └── class-rbf-site-core-family-admin.php
└── templates/
    └── product-family-item.php
```

Die Core-Klasse ist der zentrale Bootstrap/Coordinator.

Hook-basierte Module werden dort einmal instanziert.

Stateless Data-/Helper-Klassen arbeiten aktuell über statische Methoden.

---

## Taxonomie `product_family`

Custom Taxonomy für WooCommerce-Produkte.

Aktuelle Registrierung:

```text
Object Type: product
Public: true
Hierarchical: true
REST: true
Rewrite Slug: product-family
```

Die Taxonomie wird nur als Fallback registriert.

Existiert sie bereits, registriert `rbf-site-core` sie nicht erneut.

Der konkrete Taxonomie-Key wird über `RbfSiteCoreDataKeys` aufgelöst.

---

## Generische Produkt-Datenlogik

`RbfSiteCoreProducts` kapselt generische WooCommerce-Produktabfragen und Attribut-Aggregationen.

Aktuelle Aufgaben:

```text
Produkte eines Terms ermitteln
↓
WC_Product laden
↓
gewünschte interne Attribut-Aliases auflösen
↓
Attribute auslesen
↓
Werte aggregieren
↓
unique + natürlich sortieren
```

Mehrere Attribute können in einem einzigen Produktdurchlauf aggregiert werden.

Damit müssen beispielsweise:

```text
tire_dimension
chain_strength
```

nicht jeweils separat über sämtliche Produkte einer Family berechnet werden.

---

# Product-Family Learned Index

Aggregierte Product-Family-Daten werden persistent als Term Meta gespeichert.

Meta-Key:

```text
_rbf_family_index
```

Aktueller Contract:

```php
[
    'version'       => 1,
    'learned_at'    => 1787571056,
    'product_count' => 500,

    'values' => [
        'tire_dimension' => [
            '14.9-38',
            '16.9-34',
            '420/85-34',
        ],

        'chain_strength' => [
            '8.00',
            '10.00',
        ],
    ],
]
```

Wichtig:

Im gespeicherten Index werden ausschließlich die stabilen internen Aliases verwendet.

Externe Woo-/ERP-Keys wie:

```text
tire_dimension_fit
gliederstaerke
```

werden dort nicht gespeichert.

---

## Warum ein persistenter Index?

Eine Product Family kann später mehrere hundert Produkte enthalten.

Eine Live-Aggregation bei jedem Frontend-Request würde beispielsweise bedeuten:

```text
500 Produkte
× WC_Product laden
× Attribute durchsuchen
× mehrere Reifendimensionen sammeln
```

Das soll insbesondere den initialen Archive-/Hero-Render nicht blockieren.

Stattdessen werden die abgeleiteten Daten einmal bewusst gelernt und persistent am Family-Term gespeichert.

Normale Frontend-Reads greifen anschließend nur noch auf den gespeicherten Snapshot zu.

---

## `RbfSiteCoreFamilyIndex`

Die Klasse kapselt den persistenten Family-Index.

Aktuelle Kernoperationen:

```text
get($term_id)
rebuild($term_id)
learn($term_id)
delete($term_id)
```

### `rebuild()`

Erstellt den Snapshot unabhängig vom bisherigen Zustand vollständig neu.

Dabei werden:

```text
tire_dimension
chain_strength
```

in einem gemeinsamen Produktdurchlauf aggregiert.

### `learn()`

Self-Healing-Variante.

Flow:

```text
Index vorhanden?
├── ja → direkt zurückgeben
└── nein
    ↓
    Lock setzen
    ↓
    erneut prüfen
    ↓
    rebuild()
    ↓
    Lock löschen
```

Damit darf ein bislang ungelernter Term bei Bedarf selbstständig initialisiert werden.

### Lock

Für Auto-Learning wird ein kurzer WordPress-Option-Lock verwendet.

Ziel:

Mehrere parallele Besucher sollen nicht gleichzeitig denselben teuren Rebuild starten.

Der Lock besitzt zusätzlich einen TTL-Fallback, damit ein abgebrochener Request keinen dauerhaften Lock hinterlässt.

---

# Product-Family Admin

Auf der Edit-Seite eines `product_family`-Terms existiert ein Bereich:

```text
SOMA Family-Daten
```

Aktuell angezeigt werden:

* Zeitpunkt des letzten Lernens
* Produktanzahl beim letzten Lernen
* aktuell zugeordnete Produktanzahl
* Anzahl gelernter Reifendimensionen
* gelernte Gliederstärken
* Warnung bei abweichender Produktanzahl

Actions:

```text
Family-Daten neu lernen
Gelernte Daten löschen
```

Das Löschen betrifft ausschließlich:

```text
_rbf_family_index
```

Produkt- und ERP-Daten werden nicht verändert.

Bei aktivem `WP_DEBUG` kann der gespeicherte Index zusätzlich als Debug-`<pre>` ausgegeben werden.

---

## Future Admin UX

Bei ungefähr 20–40 Product Families soll später zusätzlich eine zentrale Admin-Seite entstehen.

Geplant:

```text
Product Family Data
```

mit einer tabellarischen Übersicht aller Families.

Pro Family:

* Name
* aktuelle Produktanzahl
* Produktanzahl beim letzten Lernen
* `learned_at`
* Anzahl Reifendimensionen
* gelernte Gliederstärken
* Status aktuell / möglicherweise veraltet
* Action: neu lernen
* Action: gelernte Daten löschen
* ggf. Detailansicht

Zusätzlich:

```text
Alle Families neu lernen
```

Diese Admin-Seite soll dieselbe `RbfSiteCoreFamilyIndex`-API verwenden und keine zweite Aggregationslogik implementieren.

---

# REST API

## GET Product Families

```text
GET /wp-json/rbf-site-core/v1/product-families
```

Öffentlich lesbar.

Der Endpoint liefert Product-Family-Terms und ihre bereits gelernten Daten.

Aktueller JSON-Contract:

```json
{
    "items": [
        {
            "id": 22,
            "name": "TEMPO",
            "slug": "tempo",
            "url": "https://example.test/product-family/tempo/",
            "count": 1,
            "strengths": [
                "8.00"
            ],
            "dimensions": [
                "14.9-38",
                "15.5-38",
                "16.9-34"
            ],
            "index": {
                "learned": true,
                "learned_at": 1787571056,
                "product_count": 1
            },
            "learn_url": "https://example.test/wp-json/rbf-site-core/v1/product-families/22/learn"
        }
    ]
}
```

### Ungelernte Family

Wenn noch kein gespeicherter Index existiert:

```json
{
    "strengths": [],
    "dimensions": [],
    "index": {
        "learned": false,
        "learned_at": null,
        "product_count": 0
    }
}
```

Der GET-Endpoint führt in diesem Fall ausdrücklich keinen teuren Product-Rebuild durch.

---

## POST Product Family Learn

```text
POST /wp-json/rbf-site-core/v1/product-families/{term_id}/learn
```

Dieser Endpoint dient dem Self-Healing bislang ungelernter Product Families.

Ist die Family bereits gelernt, wird der vorhandene Index zurückgegeben.

Ist sie noch nicht gelernt, wird unter Lock einmalig ein Rebuild durchgeführt und gespeichert.

Der Endpoint ist kein Force-Rebuild.

Explizite Rebuilds bleiben Admin-Aktionen.

---

# `[rbf_product_families]`

Der Shortcode rendert den Mount-Point der Product-Family-Landingpage und lädt das benötigte Plugin-JavaScript nur bei Verwendung der Komponente.

Grundstruktur:

```html
<div
    class="rbf-product-families"
    data-rbf-product-families
    data-endpoint="..."
></div>
```

Das Plugin liefert keine projektspezifischen Cards direkt als HTML aus.

---

# Product-Family Template

Das Plugin enthält ein Default-Template:

```text
templates/product-family-item.php
```

Dieses wird über:

```text
rbf_product_family_template
```

filterbar gemacht.

Das Child Theme verwendet aktuell:

```text
template-parts/product-family-item.php
```

als Override.

Funktionale DOM-Hooks bleiben `data-*`-Attribute.

CSS-Klassen gehören ausschließlich zur Präsentation und können im Theme geändert werden.

---

# JavaScript-Flow

`assets/js/product-families.js`

Flow:

```text
[data-rbf-product-families]
↓
GET Product-Family REST
↓
alle Cards sofort aus aktuellem Snapshot rendern
↓
ungelernte Families erkennen
↓
sequenziell lernen
↓
je Family POST learn_url
↓
gelernte Strengths in bestehende Card übernehmen
```

Ungelernten Families werden bewusst sequenziell verarbeitet.

Damit starten bei vielen neuen Families nicht gleichzeitig zahlreiche teure WooCommerce-Aggregationen.

Bereits gelernte Families verursachen keine Learn-Requests.

---

# Data Ownership

ERP bzw. externe Importlogik bleibt Datenhoheit für:

* Produkte
* Produktattribute
* Zuordnung zur `product_family`
* gegebenenfalls Erstellung der Product-Family-Terms

SOMA / `rbf-site-core` besitzt ausschließlich die daraus abgeleiteten Frontend-/UX-Daten.

Das gespeicherte:

```text
_rbf_family_index
```

ist daher ein SOMA-interner Derived-Data-Index und keine konkurrierende Produktdatenquelle.

Dadurch kann das Frontend unabhängig optimiert werden, ohne Änderungen am ERP-Importflow vorauszusetzen.

---

# Product-Family Archive

Das Child Theme besitzt inzwischen ein eigenes:

```text
templates/taxonomy-product_family.html
```

Der Family-Hero ist grundsätzlich an das bestehende Hero-System angebunden.

Der nächste Schritt ist:

* Name/Beschreibung weiterhin serverseitig
* aggregierte Dimensionen nicht mehr teuer im initialen Render berechnen
* Dimensionen asynchron über REST laden
* zunächst drei Dimensionen anzeigen
* restliche Dimensionen über eine Expand-Action sichtbar machen

Danach folgt das serverseitige Product Grid mit wiederverwendbaren Product Cards.

---

# Roadmap

## Product-Family Basis

* [x] `product_family` als Fallback-Taxonomie registrieren
* [x] `[rbf_product_families]`
* [x] REST-Endpoint
* [x] Default-`<template>`
* [x] Theme-Override
* [x] Plugin-JavaScript

## Datenarchitektur

* [x] zentrale Data-Key-Mappings
* [x] stabile Aliases `tire_dimension` und `chain_strength`
* [x] generische Product-Query-/Attribute-Logik
* [x] mehrere Attribute in einem Produktdurchlauf aggregieren
* [x] persistenter Family-Index
* [x] manueller Rebuild
* [x] manueller Delete
* [x] Auto-Learn mit Lock
* [x] REST liest Learned-Daten statt Live-Aggregation
* [x] Self-Healing der Landingpage

## Product-Family Archive

* [x] eigenes Taxonomy-Archive
* [x] bestehendes Hero-System verwenden
* [x] Term-Name und Beschreibung
* [x] Reifendimensionen als Learned Data verfügbar
* [ ] Hero-Dimensionen async
* [ ] 3 Werte + Expand
* [ ] Product Grid
* [ ] Product Cards

## Später

* [ ] zentrale Family-Data-Adminseite
* [ ] „Alle Families neu lernen“
* [ ] Product Single
* [ ] interaktive Reifendimensionsfilter
* [ ] Product-Item-REST-Contract
* [ ] ERP-/Import-abhängige Optimierungen nur wenn tatsächlich nötig

---

## Version

Aktuell:

```text
0.2.0
```
