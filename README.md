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



## Cart Item Notes

`rbf-site-core` erweitert WooCommerce Cart Items um:

    cart_item_note

Der Wert wird:

- über die WooCommerce Store API bereitgestellt
- über `extensionCartUpdate()` aktualisiert
- serverseitig mit `sanitize_textarea_field()` bereinigt
- auf 400 Zeichen begrenzt
- in der WooCommerce Session persistiert

Frontend-Integration:

    assets/js/cart-item-note.js

Das Script ergänzt im WooCommerce Cart Block pro Position ein Notizfeld und
synchronisiert Änderungen über den offiziellen Store-API-Update-Flow.

Die Notiz ist Teil des laufenden Cart-Zustands und kann später von
`rbf-shop-documents` in persistente Quote-Snapshots übernommen werden.



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

    0.2.6



## HISTORY


## 0.2.6

- Added persistent `cart_item_note` support for WooCommerce Cart Blocks
- Added Store API extension data for cart item notes
- Added Store API update callback for cart item notes
- Added server-side sanitization and 400 character limit
- Added WooCommerce session persistence for cart item notes
- Added functional Cart Block textarea integration
- Added React-mount handling for asynchronous WooCommerce Cart rendering


## 0.2.5

### B2B Branding

- Neue zentrale Branding-Komponente `RbfSiteCoreB2BBranding`.
- Unterstützte Rollen:
  - `shop_manager`
  - `b2b_haendler`
  - `b2b_vertrieb`
- Firmenlogo pro B2B-User als WordPress-Attachment.
- Unterstützte Logoformate:
  - PNG
  - WebP
  - SVG
- Maximale Logo-Dateigröße: 200 KB.
- SVG-Dateien werden beim Upload serverseitig geprüft und sanitisiert.
- Optionales Inline-Rendering von SVG-Logos.
- Inline-SVG-Ausgabe wird nochmals strenger sanitisiert:
  - `<script>` entfernt
  - `<style>` entfernt
  - `foreignObject`, `iframe`, `object`, `embed`, `audio`, `video` entfernt
  - Eventhandler wie `onclick` / `onload` entfernt
  - externe `href` / `xlink:href` entfernt
  - `javascript:` / `vbscript:` entfernt

### WordPress User Profile

- B2B-Branding-Feld in WordPress-Benutzerprofilen.
- Administratoren können Händlerlogos hochladen, ersetzen und entfernen.
- Administratoren können für SVG-Logos optional Inline-Rendering aktivieren.
- Logo wird als Attachment-ID im User-Meta gespeichert:
  - `rbf_b2b_logo_id`
  - `rbf_b2b_logo_inline`

### WooCommerce My Account

- Firmenlogo wird über WooCommerce-Hooks in `My Account → Account Details` integriert.
- Kein WooCommerce-Template-Override notwendig.
- B2B-User sehen ihr aktuelles Firmenlogo.
- User mit Capability `rbf_can_access_b2b_admin` dürfen:
  - Logo hochladen
  - Logo ersetzen
  - Logo entfernen
- B2B-User ohne diese Capability sehen das Logo ausschließlich read-only.
- Die bestehende Upload-/Validierungslogik wird für WP-Backend und My Account gemeinsam verwendet.

### Globales SOMA Branding

- Neue Admin-Seite:
  - `Einstellungen → SOMA Branding`
- Globales Standard-SOMA-Logo als WordPress-Attachment.
- Unterstützt ebenfalls PNG, WebP und SVG bis maximal 200 KB.
- Nutzt dieselbe zentrale Upload- und SVG-Sanitizing-Pipeline wie B2B-Logos.
- Optionen:
  - `rbf_site_logo_id`
  - `rbf_site_logo_inline`
- Administrator kann:
  - Standardlogo hochladen
  - Standardlogo ersetzen
  - Standardlogo entfernen
  - SVG-Inline-Rendering aktivieren

### Branding API

Neue zentrale Getter / Renderer für Theme und weitere Komponenten:

- `RbfSiteCoreB2BBranding::get_logo_id()`
- `RbfSiteCoreB2BBranding::get_logo_markup()`
- `RbfSiteCoreB2BBranding::get_site_logo_id()`
- `RbfSiteCoreB2BBranding::get_site_logo_markup()`
- `RbfSiteCoreB2BBranding::get_effective_logo_id()`
- `RbfSiteCoreB2BBranding::get_effective_logo_markup()`

Logo-Priorität:

1. eigenes B2B-Händlerlogo
2. globales SOMA-Standardlogo
3. kein Logo → Theme kann auf bisherigen Site Title zurückfallen


## 0.2.4

- Product-Family-REST-Items über `rbf_site_core_product_family_rest_item` erweiterbar gemacht
- Product-Family-Cards unterstützen externe Preview-Bilder
- `product-families.js` rendert ein Preview-Bild, wenn `xr_preview_url` vorhanden ist
- bestehender Placeholder bleibt als Fallback erhalten