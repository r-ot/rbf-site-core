# RBF Site Core

Site-spezifische Datenstrukturen, REST-Endpunkte und Fallback-Registrierungen für den WooCommerce-Shop.

## Architektur

Grundsätzliche Trennung:

- **Plugin** = Daten, Businesslogik, REST-Endpunkte
- **Theme** = Darstellung, CSS, JavaScript, HTML-`<template>`-Strukturen

REST-Endpunkte liefern grundsätzlich strukturierte JSON-Daten und kein fertiges HTML.

Die Darstellung dynamischer Inhalte erfolgt im Child Theme über native HTML-`<template>`-Elemente mit `data-*`-Attributen.

### Ownership der Product-Family-Komponente

Das Plugin besitzt die funktionale Product-Family-Komponente:
- Shortcode / Mount-Point
- REST-Endpunkt
- Fetch-/DOM-JavaScript
- Default-Template als Fallback
Das Child Theme besitzt die projektspezifische Darstellung.
Das Plugin-JavaScript arbeitet ausschließlich mit `data-*`-Hooks und ist dadurch nicht von den CSS-Klassen des Themes abhängig.
Der REST-Endpunkt wird vom Shortcode über `data-endpoint` an das JavaScript übergeben. Das JavaScript enthält daher keine fest codierte WordPress-REST-URL.

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
│   └── class-rbf-site-core-shortcodes.php
└── templates/
    └── product-family-item.php

```

## Taxonomies
product_family
Custom Taxonomy für WooCommerce-Produkte.
Registrierung:
Object Type: product
Public: true
Hierarchical: true
REST: true
Rewrite Slug: product-family

Die Taxonomie wird nur als Fallback registriert:
if (taxonomy_exists('product_family')) {
    return;
}
Andere Plugins können product_family daher vorher selbst registrieren.

## Shortcodes

### `[rbf_product_families]`

Rendert den Mount-Point für die dynamische Ausgabe der Product Families und lädt das benötigte JavaScript nur dann, wenn der Shortcode tatsächlich verwendet wird.

Aktuelle Ausgabe:

```html
<div
    class="rbf-product-families"
    data-rbf-product-families
    data-endpoint="https://example.test/wp-json/rbf-site-core/v1/product-families"
></div>
```
Der Shortcode selbst rendert keine Product-Family-Cards.



## REST API
Product Families
GET /wp-json/rbf-site-core/v1/product-families
Aktuell öffentlich lesbar.
Der Endpoint liefert die vorhandenen Terms der Taxonomie product_family.
Aktueller JSON Contract
{
    "items": [
        {
            "id": 20,
            "name": "SUPER STIFT",
            "slug": "super_stift",
            "url": "http://example.local/product-family/super_stift/",
            "count": 1
        }
    ]
}

Aktuelle Felder:
id – Term-ID
name – Name der Product Family
slug – Term-Slug
url – Term-Archiv-URL
count – Anzahl zugeordneter Produkte


## Frontend Contract

### Mount-Point

```html
[data-rbf-product-families]
```

Der Shortcode stellt folgenden Mount-Point bereit:
[data-rbf-product-families]
Das Attribut data-endpoint enthält die REST-URL, von der die Komponente ihre Daten lädt.

### Product-Family-Template
Das Plugin enthält ein Default-Template:
templates/product-family-item.php
Dieses wird nur ausgegeben, wenn [rbf_product_families] auf der aktuellen Seite tatsächlich gerendert wurde.
Das Template wird über folgenden Filter auflösbar gemacht:

rbf_product_family_template

Das Child Theme kann dadurch ein eigenes Template bereitstellen, ohne die Plugin-Logik zu verändern.

### Aktueller Theme-Override:

twentytwentyfive-child/
└── template-parts/
    └── product-family-item.php

Beispiel für den Theme-Filter:

add_filter('rbf_product_family_template', function($template) {
    $theme_template = get_stylesheet_directory()
        . '/template-parts/product-family-item.php';
    if (is_readable($theme_template)) {
        return $theme_template;
    }
    return $template;
});

Das native HTML-Template verwendet folgende funktionale Hooks:
```html
<template data-rbf-product-family-template>
    <article data-rbf-product-family-item>
        <a data-rbf-family-link>
            <h3 data-rbf-family-title></h3>


            <div data-rbf-family-strengths></div>


            <span data-rbf-family-strengths-text></span>
        </a>
    </article>
</template>
```

CSS-Klassen gehören zur Darstellung und dürfen vom Theme unabhängig von diesen data-*-Hooks geändert werden.

## JavaScript-Flow

assets/js/product-families.js:
findet [data-rbf-product-families]
liest den REST-Endpunkt aus data-endpoint
lädt die Product Families als JSON
findet [data-rbf-product-family-template]
klont template.content mit cloneNode(true)
befüllt die geklonten Elemente über data-*-Hooks
hängt die fertigen Cards in den Mount-Point ein

REST/API liefert ausschließlich Daten.

Plugin-JavaScript übernimmt die funktionale DOM-Erzeugung.

Das Child Theme bestimmt das visuelle Markup und Styling.


## Geplante Product-Family-Daten

Der JSON Contract soll schrittweise erweitert werden.

Geplant:

{
	"items": [
		{
			"id": 20,
			"name": "SUPER STIFT",
			"slug": "super_stift",
			"url": "http://example.local/product-family/super_stift/",
			"count": 1,
			"strengths": [
				"11",
				"14",
				"16"
			]
		}
	]
}

## "strengths"
- `strengths` – eindeutige Gliedstärken der Produkte innerhalb dieser Product Family

### Ermittlung von `strengths`
Im aktuellen Prototyp werden die Werte zur Laufzeit aus den WooCommerce-Produkten der jeweiligen `product_family` ermittelt.
Quelle ist das Produktattribut:
`gliederstaerke`
Die aktuelle Implementierung dient der Frontend-/API-Entwicklung. Die interne Datenquelle darf später durch eine optimierte Relation oder Lookup-Tabelle ersetzt werden, solange der JSON Contract erhalten bleibt.
Soll die verfügbaren Gliedstärken aller Produkte einer Product Family enthalten.

Für den aktuellen Prototyp werden diese Werte direkt aus den zugeordneten WooCommerce-Produkten ermittelt.

Die endgültige Datenarchitektur kann später geändert werden, ohne den JSON Contract oder das Frontend ändern zu müssen.


# Roadmap

- [x] `product_family` als Fallback-Taxonomie registrieren
- [x] REST-Endpoint für Product Families
- [x] `strengths` im Product-Family-Endpoint ergänzen
- [x] Shortcode `[rbf_product_families]`
- [x] Product-Family-JavaScript ins Plugin verschieben
- [x] REST-Endpoint über `data-endpoint` an die Komponente übergeben
- [x] Default-`<template>` im Plugin
- [x] filterbarer Theme-Override für das Product-Family-Template
- [ ] Product-Family-Template nach Figma stylen
- [ ] Product-Family-Bild integrieren
- [ ] Einsatzbereiche / Icons integrieren
- [ ] Product-Item-REST-Endpoint
- [ ] Product-Item-`<template>`