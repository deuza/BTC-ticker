<?php
/* ========================================
   CONFIG - seules variables à modifier
   ======================================== */
$BTC           = 0.12345678;   // contenu du wallet en BTC
$DEFAULT_CHART = "d";          // période par défaut : h|d|w|m|ytd|y|5y|max

$MBTC = $BTC * 1000;
$SATS = round($BTC * 100000000);

// mapping label humain -> valeur data-days
$CHART_MAP = [
  "h"   => "0.0416",
  "d"   => "1",
  "w"   => "7",
  "m"   => "30",
  "ytd" => "ytd",
  "y"   => "365",
  "5y"  => "1825",
  "max" => "max",
];
$DEFAULT_DAYS = $CHART_MAP[$DEFAULT_CHART] ?? "1";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<link rel="icon" href="favicon.ico">
<title>BTC Wallet Value</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: monospace;
  background: #000;
  color: #0f0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
canvas#matrix {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 0;
  pointer-events: none;
}
pre.ascii {
  position: fixed;
  top: 10px;
  left: 10px;
  z-index: 2;
  color: #0f0;
  white-space: pre;
  opacity: 0.6;
  pointer-events: none;
  font-size: 15px;
}
.content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 700px;
  gap: 20px;
}
h2 {
  color: #0f0;
  font-size: 20px;
}
.panel {
  background: rgba(0,0,0,0.85);
  border: 1px solid #0f0;
  padding: 20px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
}
.inputs {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.inputs div {
  display: flex;
  align-items: center;
  gap: 8px;
}
label {
  width: 55px;
  text-align: right;
  color: #0a0;
  font-size: 16px;
}
input {
  font-family: monospace;
  font-size: 18px;
  background: #000;
  color: #0f0;
  border: 1px solid #0f0;
  padding: 5px 8px;
  width: 200px;
}
input:focus { outline: none; border-color: #5f5; }
#result {
  color: #0f0;
  padding: 0;
  font-size: 16px;
  list-style: none;
}
#result li::before {
  content: "> ";
  color: #0a0;
}
#result li { padding: 3px 0; }
#ticker {
  font-size: 14px;
  color: #0f0;
  background: rgba(0,255,0,0.08);
  border: 1px solid #070;
  padding: 6px 14px;
  text-align: center;
}
#api-status {
  font-size: 11px;
  text-align: center;
  min-height: 14px;
}
#api-status.ok { color: #0a0; }
#api-status.warn { color: #fa0; }
#api-status.err { color: #f00; }
.chart-section {
  width: 100%;
  background: rgba(0,0,0,0.85);
  border: 1px solid #0f0;
  padding: 15px;
}
.chart-buttons {
  display: flex;
  gap: 6px;
  justify-content: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
}
.chart-buttons button {
  font-family: monospace;
  font-size: 13px;
  background: #000;
  color: #0a0;
  border: 1px solid #0a0;
  padding: 4px 10px;
  cursor: pointer;
}
.chart-buttons button:hover,
.chart-buttons button.active {
  background: #0f0;
  color: #000;
}
.chart-wrap {
  width: 100%;
  height: 250px;
  position: relative;
}
.chart-wrap canvas { width: 100% !important; height: 100% !important; }
#chart-status {
  text-align: center;
  font-size: 12px;
  color: #070;
  margin-top: 8px;
}
#chart-status.up { color: #0f0; }
#chart-status.down { color: #f00; }
</style>
</head>
<body>
<canvas id="matrix"></canvas>
<pre class="ascii">

⠀⠀⠀⠀⠀⠀⠀⠀⣀⣤⣴⣶⣾⣿⣿⣿⣿⣷⣶⣦⣤⣀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⣠⣴⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣦⣄⠀⠀⠀⠀⠀
⠀⠀⠀⣠⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣄⠀⠀⠀
⠀⠀⣴⣿⣿⣿⣿⣿⣿⣿⠟⠿⠿⡿⠀⢰⣿⠁⢈⣿⣿⣿⣿⣿⣿⣿⣿⣦⠀⠀
⠀⣼⣿⣿⣿⣿⣿⣿⣿⣿⣤⣄⠀⠀⠀⠈⠉⠀⠸⠿⣿⣿⣿⣿⣿⣿⣿⣿⣧⠀
⢰⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡏⠀⠀⢠⣶⣶⣤⡀⠀⠈⢻⣿⣿⣿⣿⣿⣿⣿⡆
⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠃⠀⠀⠼⣿⣿⡿⠃⠀⠀⢸⣿⣿⣿⣿⣿⣿⣿⣷
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡟⠀⠀⢀⣀⣀⠀⠀⠀⠀⢴⣿⣿⣿⣿⣿⣿⣿⣿⣿
⢿⣿⣿⣿⣿⣿⣿⣿⢿⣿⠁⠀⠀⣼⣿⣿⣿⣦⠀⠀⠈⢻⣿⣿⣿⣿⣿⣿⣿⡿
⠸⣿⣿⣿⣿⣿⣿⣏⠀⠀⠀⠀⠀⠛⠛⠿⠟⠋⠀⠀⠀⣾⣿⣿⣿⣿⣿⣿⣿⠇
⠀⢻⣿⣿⣿⣿⣿⣿⣿⣿⠇⠀⣤⡄⠀⣀⣀⣀⣀⣠⣾⣿⣿⣿⣿⣿⣿⣿⡟⠀
⠀⠀⠻⣿⣿⣿⣿⣿⣿⣿⣄⣰⣿⠁⢀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠟⠀⠀
⠀⠀⠀⠙⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠋⠀⠀⠀
⠀⠀⠀⠀⠀⠙⠻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠟⠋⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠉⠛⠻⠿⢿⣿⣿⣿⣿⡿⠿⠟⠛⠉⠀⠀⠀⠀⠀⠀⠀⠀

</pre>
<div class="content">
<h2>&#8383; Wallet Value</h2>
<div class="panel">
  <div class="inputs">
    <div><label>BTC:</label><input id="btc" value="<?= number_format($BTC, 8, '.', '') ?>"></div>
    <div><label>mBTC:</label><input id="mbtc" value="<?= number_format($MBTC, 5, '.', '') ?>"></div>
    <div><label>sats:</label><input id="sats" value="<?= $SATS ?>"></div>
  </div>
  <ul id="result"><li>Fetching price...</li></ul>
  <div id="ticker">Connecting...</div>
  <div id="api-status"></div>
</div>
<div class="chart-section">
  <div class="chart-buttons" id="chart-buttons">
    <button data-days="0.0416"<?= $DEFAULT_DAYS === "0.0416" ? ' class="active"' : '' ?>>1h</button>
    <button data-days="1"<?= $DEFAULT_DAYS === "1" ? ' class="active"' : '' ?>>1d</button>
    <button data-days="7"<?= $DEFAULT_DAYS === "7" ? ' class="active"' : '' ?>>7d</button>
    <button data-days="30"<?= $DEFAULT_DAYS === "30" ? ' class="active"' : '' ?>>1m</button>
    <button data-days="ytd"<?= $DEFAULT_DAYS === "ytd" ? ' class="active"' : '' ?>>YTD</button>
    <button data-days="365"<?= $DEFAULT_DAYS === "365" ? ' class="active"' : '' ?>>1y</button>
    <button data-days="1825"<?= $DEFAULT_DAYS === "1825" ? ' class="active"' : '' ?>>5y</button>
    <button data-days="max"<?= $DEFAULT_DAYS === "max" ? ' class="active"' : '' ?>>All</button>
  </div>
  <div class="chart-wrap">
    <canvas id="priceChart"></canvas>
  </div>
  <div id="chart-status"></div>
</div>
</div>
<script>
/* --- Matrix rain --- */
(function() {
  const c = document.getElementById("matrix");
  const ctx = c.getContext("2d");
  const chars = "01₿$€¥£₹⚡ΣΩπ∞∂√∫≈≠±×÷<>{}[]#@&%!?abcdefghijklmnopqrstuvwxyz";
  let cols, drops;
  function resize() {
    c.width = window.innerWidth;
    c.height = window.innerHeight;
    cols = Math.floor(c.width / 14);
    drops = Array(cols).fill(1);
  }
  resize();
  window.addEventListener("resize", resize);
  setInterval(() => {
    ctx.fillStyle = "rgba(0, 0, 0, 0.05)";
    ctx.fillRect(0, 0, c.width, c.height);
    ctx.fillStyle = "#0f0";
    ctx.font = "14px monospace";
    for (let i = 0; i < cols; i++) {
      const ch = chars[Math.floor(Math.random() * chars.length)];
      ctx.fillText(ch, i * 14, drops[i] * 14);
      if (drops[i] * 14 > c.height && Math.random() > 0.975) drops[i] = 0;
      drops[i]++;
    }
  }, 50);
})();

/* ============================
   STATE
   ============================ */
const $ = id => document.getElementById(id);
const SATS = 100000000;
let rates = null;
let lastUpdate = null;
let failCount = 0;
let currentPeriod = <?= json_encode($DEFAULT_DAYS === "max" || $DEFAULT_DAYS === "ytd" ? $DEFAULT_DAYS : (float)$DEFAULT_DAYS) ?>;
let chart = null;
let chartLoading = false;

/* ============================
   INPUT SYNC
   ============================ */
function fromBTC(btc) {
  $("mbtc").value = (btc * 1000).toFixed(5);
  $("sats").value = Math.round(btc * SATS);
}
function fromMBTC(mbtc) {
  const btc = mbtc / 1000;
  $("btc").value = btc.toFixed(8);
  $("sats").value = Math.round(btc * SATS);
}
function fromSats(sats) {
  const btc = sats / SATS;
  $("btc").value = btc.toFixed(8);
  $("mbtc").value = (btc * 1000).toFixed(5);
}

function render() {
  const btc = parseFloat($("btc").value) || 0;
  const sats = Math.round(btc * SATS);
  if (!rates) return;
  const eur = btc * rates.eur;
  const usd = btc * rates.usd;
  $("result").innerHTML =
    "<li>" + btc.toFixed(8) + " BTC</li>" +
    "<li>" + eur.toFixed(2) + " EUR</li>" +
    "<li>" + usd.toFixed(2) + " USD</li>" +
    "<li>" + sats.toLocaleString() + " sats</li>";
  const tk = $("ticker");
  tk.innerText = "1 BTC = " + rates.eur.toLocaleString() + " EUR / " +
    rates.usd.toLocaleString() + " USD";
  tk.className = "";
}

$("btc").addEventListener("input",  () => { fromBTC(parseFloat($("btc").value)   || 0); render(); });
$("mbtc").addEventListener("input", () => { fromMBTC(parseFloat($("mbtc").value) || 0); render(); });
$("sats").addEventListener("input", () => { fromSats(parseFloat($("sats").value) || 0); render(); });

/* ============================
   FETCH PRICE - Kraken Ticker
   GET /0/public/Ticker?pair=XBTEUR,XBTUSD
   result.XXBTZEUR.c[0] = last trade price EUR
   result.XXBTZUSD.c[0] = last trade price USD
   ============================ */
async function fetchPrice() {
  const as = $("api-status");
  try {
    const r = await fetch("https://api.kraken.com/0/public/Ticker?pair=XBTEUR,XBTUSD");
    if (!r.ok) throw new Error("HTTP " + r.status);
    const j = await r.json();
    if (j.error && j.error.length) throw new Error(j.error[0]);
    const eur = parseFloat(j.result["XXBTZEUR"].c[0]);
    const usd = parseFloat(j.result["XXBTZUSD"].c[0]);
    rates = { eur, usd };
    lastUpdate = new Date();
    failCount = 0;
    as.className = "ok";
    as.innerText = "kraken | updated " + lastUpdate.toLocaleDateString() + " " + lastUpdate.toLocaleTimeString();
  } catch(e) {
    failCount++;
    if (lastUpdate) {
      const ago = Math.round((Date.now() - lastUpdate) / 60000);
      as.className = ago > 5 ? "err" : "warn";
      as.innerText = "⚠ " + e.message + " | last update " + lastUpdate.toLocaleDateString() + " " + lastUpdate.toLocaleTimeString();
    } else {
      as.className = "err";
      as.innerText = "⚠ " + e.message + " | no data yet";
      $("result").innerHTML = "<li>⚠ Kraken: " + e.message + "</li>";
      $("ticker").innerText = "Waiting for API...";
    }
  }
  render();
}

/* ============================
   CHART - Kraken OHLC
   GET /0/public/OHLC?pair=XBTEUR&interval=X&since=Y
   Intervals (minutes): 1, 5, 15, 30, 60, 240, 1440, 10080, 21600
   Max 720 bougies par requete.
   Format: [time(unix sec), open, high, low, close, vwap, volume, count]
   XBTEUR dispo depuis 2013 => All = vrai historique complet
   ============================ */

function getKrakenParams(period) {
  const now = Math.floor(Date.now() / 1000);
  if (period === 0.0416) return { interval: 1,     since: now - 3600,       numDays: 1     };
  if (period === 1)      return { interval: 5,     since: now - 86400,      numDays: 1     };
  if (period === 7)      return { interval: 60,    since: now - 7*86400,    numDays: 7     };
  if (period === 30)     return { interval: 240,   since: now - 30*86400,   numDays: 30    };
  if (period === 365)    return { interval: 1440,  since: now - 365*86400,  numDays: 365   };
  if (period === 1825)   return { interval: 10080, since: now - 1825*86400, numDays: 1825  };
  if (period === "max")  return { interval: 10080, since: 0,                numDays: 99999 };
  if (period === "ytd") {
    const jan1 = Math.floor(new Date(new Date().getFullYear(), 0, 1).getTime() / 1000);
    const numDays = Math.ceil((now - jan1) / 86400);
    const interval = numDays <= 60 ? 240 : 1440;
    return { interval, since: jan1, numDays };
  }
  return { interval: 1440, since: now - 365*86400, numDays: 365 };
}

function formatLabel(ts, numDays) {
  const d = new Date(ts);
  if (numDays <= 1)   return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  if (numDays <= 30)  return d.toLocaleDateString([], { day: "2-digit", month: "short" });
  return d.toLocaleDateString([], { month: "short", year: "2-digit" });
}

async function loadChart(period) {
  if (chartLoading) return;
  chartLoading = true;
  currentPeriod = period;
  $("chart-status").innerText = "Loading...";
  if (chart) { chart.destroy(); chart = null; }

  try {
    const { interval, since, numDays } = getKrakenParams(period);
    const url = "https://api.kraken.com/0/public/OHLC?pair=XBTEUR&interval=" + interval + "&since=" + since;
    const r = await fetch(url);
    if (!r.ok) throw new Error("HTTP " + r.status);
    const j = await r.json();
    if (j.error && j.error.length) throw new Error(j.error[0]);

    // La cle peut etre "XBTEUR" ou "XXBTZEUR" selon Kraken - on prend la premiere qui n'est pas "last"
    const key = Object.keys(j.result).find(k => k !== "last");
    if (!key) throw new Error("No pair in response");
    const candles = j.result[key];
    if (!candles || !candles.length) throw new Error("No data");

    // Kraken time = unix secondes -> *1000 pour JS Date
    const labels = candles.map(k => formatLabel(k[0] * 1000, numDays));
    const data   = candles.map(k => parseFloat(k[4])); // close price
    const first = data[0], last = data[data.length - 1];
    const pct = ((last - first) / first * 100).toFixed(2);

    chart = new Chart($("priceChart"), {
      type: "line",
      data: {
        labels: labels,
        datasets: [{
          data: data,
          borderColor: "#0f0",
          borderWidth: 1.5,
          pointRadius: 0,
          fill: { target: "origin", above: "rgba(0,255,0,0.05)" },
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 400 },
        interaction: { intersect: false, mode: "index" },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "#000",
            borderColor: "#0f0",
            borderWidth: 1,
            titleFont: { family: "monospace" },
            bodyFont: { family: "monospace" },
            callbacks: {
              label: c => c.parsed.y.toLocaleString(undefined, {
                minimumFractionDigits: 2, maximumFractionDigits: 2
              }) + " EUR"
            }
          }
        },
        scales: {
          x: {
            ticks: { color: "#070", maxTicksLimit: 8, maxRotation: 0,
                     font: { family: "monospace", size: 10 } },
            grid: { color: "rgba(0,255,0,0.1)" }
          },
          y: {
            position: "right",
            ticks: { color: "#070", font: { family: "monospace", size: 10 },
                     callback: v => v.toLocaleString() + " €" },
            grid: { color: "rgba(0,255,0,0.1)" }
          }
        }
      }
    });

    const cs = $("chart-status");
    cs.className = pct >= 0 ? "up" : "down";
    cs.innerText = (pct >= 0 ? "▲ +" : "▼ ") + pct + "% | " +
      last.toLocaleString(undefined, { maximumFractionDigits: 0 }) + " EUR";

  } catch(e) {
    const cs = $("chart-status");
    cs.className = "";
    cs.innerText = "Error: " + e.message;
  }
  chartLoading = false;
}

/* Chart buttons with debounce */
let chartTimer = null;
$("chart-buttons").addEventListener("click", e => {
  if (e.target.tagName !== "BUTTON") return;
  document.querySelectorAll(".chart-buttons button").forEach(b => b.classList.remove("active"));
  e.target.classList.add("active");
  const d = e.target.dataset.days;
  const period = (d === "max" || d === "ytd") ? d : parseFloat(d);
  clearTimeout(chartTimer);
  chartTimer = setTimeout(() => loadChart(period), 300);
});

/* ============================
   INIT + REFRESH
   Tout sur Kraken - ticker + charts
   Prix : init + toutes les 60s
   Chart : init 1.5s apres prix, refresh 5s apres prix
   ============================ */
fetchPrice();
setTimeout(() => loadChart(currentPeriod), 1500);

setInterval(() => {
  fetchPrice();
  setTimeout(() => loadChart(currentPeriod), 5000);
}, 60000);
</script>
</body>
</html>
