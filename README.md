# RBF Site Core

Site-spezifische Datenlogik, REST-Endpunkte und funktionale Komponenten
für das SOMA WooCommerce-Projekt.

## Architektur
- WooCommerce / WordPress = Source of Truth
- rbf-site-core = Datenlogik, Aggregation, REST, funktionales JS
- Childtheme = Darstellung, CSS, Templates
- rbf-xr-viewer = XR-/360°-Viewer

REST liefert strukturierte Daten, kein projektspezifisches HTML.

## Daten-Aliases

Interne stabile Aliases:

    tire_dimension
    chain_strength

Aktuelles Mapping:

   tire_dimension
	→ tire_dimension_fit
	→ dimension
	→ dimensionen

	chain_strength
	→ gliederstaerke


Wichtig dabei: Der Fallback gilt pro Produkt. Ein Produkt mit tire_dimension_fit verwendet diesen Key; fehlt er dort, wird dimension probiert. Dadurch funktionieren gemischte Importstände. (0.2.2)


Product-Family-Taxonomie:

    product_family → product_family

Externe ERP-/WooCommerce-Keys werden ausschließlich zentral gemappt.

## Struktur

    rbf-site-core/
    ├── rbf-site-core.php
    ├── assets/js/
    │   ├── product-families.js
    │   └── product-family-data.js
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

## Product Families

`product_family` wird als Fallback-Taxonomie für WooCommerce-Produkte
registriert, falls sie noch nicht existiert.

`RbfSiteCoreProducts` kapselt Produktabfragen und die Aggregation von
Produktattributen.

Mehrere Attribute können in einem gemeinsamen Produktdurchlauf gelesen werden.

## Family Index

Aggregierte Family-Daten werden persistent im Term Meta gespeichert:

    _rbf_family_index

Enthalten sind unter anderem:

- Version
- learned_at
- product_count
- tire_dimension
- chain_strength

Der Index verwendet ausschließlich interne Aliases.

Kernoperationen:

    get()
    learn()
    rebuild()
    delete()

`learn()` erstellt einen fehlenden Index einmalig unter Lock.
Normale Frontend-Reads verwenden anschließend nur den gespeicherten Snapshot.

## Admin

Auf der Product-Family-Editseite können gelernte Daten:

- eingesehen
- neu gelernt
- gelöscht

werden.

Bei abweichender Produktanzahl wird ein möglicherweise veralteter Index
kenntlich gemacht.

## REST API

### Product Families

    GET /wp-json/rbf-site-core/v1/product-families

Liefert Family-Terms und deren gelernte Daten.

### Learn Family

    POST /wp-json/rbf-site-core/v1/product-families/{term_id}/learn

Initialisiert bislang ungelernte Family-Daten.
Bereits gelernte Daten werden ohne Force-Rebuild zurückgegeben.

## Frontend-Komponenten

### `[rbf_product_families]`

Rendert den Mount-Point der Product-Family-Landingpage.

`product-families.js`:

    REST Snapshot
    → Cards rendern
    → ungelernte Families sequenziell lernen
    → Card-Daten aktualisieren

### Product-Family-Daten

`product-family-data.js` lädt generische gelernte Family-Daten für
Frontend-Komponenten.

Aktuell werden damit unter anderem die Reifendimensionen im
Product-Family-Hero asynchron gerendert.

Die Darstellung bleibt Aufgabe des Childthemes.

## Data Ownership

ERP / Import:

- Produkte
- Produktattribute
- Product-Family-Zuordnung

SOMA / rbf-site-core:

- daraus abgeleitete Frontend-/UX-Daten
- `_rbf_family_index`

Der Family Index ist keine konkurrierende Produktdatenquelle.

## Product-Family Archive

Aktueller Stand:

- eigenes Taxonomy-Archive
- bestehendes Hero-System
- Term-Name und Beschreibung serverseitig
- Reifendimensionen aus persistentem Family Index
- asynchrones Rendering
- initial drei Dimensionen
- Expand / Collapse weiterer Dimensionen

Als Nächstes:

- Product Grid
- wiederverwendbare Product Cards

## Roadmap

- [x] Product-Family Landingpage
- [x] zentrale Data-Key-Mappings
- [x] persistenter Family Index
- [x] Admin Learn/Delete
- [x] REST Self-Healing
- [x] Product-Family Archive Hero
- [x] asynchrone Hero-Dimensionen
- [ ] Product Grid
- [ ] Product Cards
- [ ] zentrale Family-Data-Adminseite
- [ ] Product Single
- [ ] interaktive Dimensionsfilter

## Version

    0.2.4



## HISTORY


## 0.2.4

- Product-Family-REST-Items über `rbf_site_core_product_family_rest_item` erweiterbar gemacht
- Product-Family-Cards unterstützen externe Preview-Bilder
- `product-families.js` rendert ein Preview-Bild, wenn `xr_preview_url` vorhanden ist
- bestehender Placeholder bleibt als Fallback erhalten