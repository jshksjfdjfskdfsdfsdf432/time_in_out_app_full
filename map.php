<!DOCTYPE html>
<html>
<head>
  <title>Map View</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <style>
    #map { height: 100vh; }
  </style>
</head>
<body>
<div id="map"></div>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
  const urlParams = new URLSearchParams(window.location.search);
  const lat = parseFloat(urlParams.get('lat'));
  const lon = parseFloat(urlParams.get('lon'));
  const label = urlParams.get('label') || "Location";

  var map = L.map('map').setView([lat, lon], 18);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 20
  }).addTo(map);

  L.marker([lat, lon]).addTo(map).bindPopup(label).openPopup();
</script>
</body>
</html>