# DRAFT: Panel pattern - map field with geocoding

## Goal

Create a map-based field that lets editors search for locations, drop a marker, and store a structured location object.

## Inputs to ask for

- Tile provider and geocoding provider
- API tokens and allowed origins
- Default center, zoom limits, and marker behavior
- Value shape to store (lat, lon, address, city, country, etc.)

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_blueprints_index
- kirby_blueprint_read
- kirby://extension/panel-fields

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/index.js or site/plugins/<plugin>/src/index.js
- site/plugins/<plugin>/src/components/<MapField>.vue
- site/plugins/<plugin>/index.css

## Implementation steps

1. Define field props for tokens, providers, and display options; decode stored YAML into an object.
2. Embed a map library in the field component and initialize with center and zoom props.
3. On search input, call external geocoding endpoints and map the response into the stored value shape.
4. Keep UI state for collapse, marker drag, and zoom persistence.
5. Prefer kirbyup for bundling the map library; if prebundled, isolate map logic in the field component.

## Examples

- Autocomplete search that selects a geocoding result and updates the marker.
- Marker drag that updates lat/lon and clears address fields until reverse geocoding is added.
- Optional zoom persistence stored alongside the location data.

## Panel JS (K5)

```js
// site/plugins/acme-map-field/src/components/MapField.vue (K5/Vue 2)
const MapField = {
  props: {
    value: Object,
    token: String,
    center: Object,
    zoom: Object,
    tiles: String,
  },
  data() {
    return {
      map: null,
      marker: null,
      query: '',
    };
  },
  computed: {
    mapId() {
      return `map-${this._uid}`;
    },
    tileUrl() {
      return `https://api.mapbox.com/styles/v1/${this.tiles}/tiles/256/{z}/{x}/{y}?access_token=${this.token}`;
    },
  },
  mounted() {
    this.map = L.map(this.mapId).setView([this.center.lat, this.center.lon], this.zoom.default);
    L.tileLayer(this.tileUrl, { attribution: 'Map data' }).addTo(this.map);
    if (this.value && this.value.lat) this.setMarker(this.value.lat, this.value.lon);
  },
  methods: {
    async geocode() {
      if (!this.query) return;
      const url = `https://geocode.example/v1?q=${encodeURIComponent(this.query)}`;
      const res = await fetch(url);
      const data = await res.json();
      const hit = data.results[0];
      if (!hit) return;
      this.updateValue({
        lat: hit.lat,
        lon: hit.lon,
        address: hit.address,
        city: hit.city,
        country: hit.country,
      });
      this.setMarker(hit.lat, hit.lon);
    },
    setMarker(lat, lon) {
      if (this.marker) this.map.removeLayer(this.marker);
      this.marker = L.marker([lat, lon], { draggable: true }).addTo(this.map);
      this.marker.on('dragend', () => {
        const pos = this.marker.getLatLng();
        this.updateValue({ ...this.value, lat: pos.lat, lon: pos.lng });
      });
    },
    updateValue(next) {
      this.$emit('input', next);
    },
  },
};

panel.plugin('acme/map-field', {
  fields: {
    map: MapField,
  },
});
```

## Verification

- Map renders with correct tiles and attribution.
- Search results update the marker and stored value.
- Value storage matches the expected object shape in content files.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-fields
- https://getkirby.com/docs/reference/plugins/ui

## Version notes (K5/K6)

- K5: Vue 2 component; map libraries may rely on older bundlers.
- K6: Vue 3 component; confirm the map library works in the Panel iframe.
- K5 -> K6: rebuild the map bundle and re-test geocoding requests.
