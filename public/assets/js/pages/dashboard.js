/* =========================================================
   REWORK: TARGET (TOTAL COLLECTIBLE) is now on NODE ID
   ✅ 3 selects inside .flex.gap-2:
      [0] Subcon, [1] Project, [2] Node
   ✅ Bar chart shows:
      - Strand Collected (green) + Working Hours (red)
      - Filters by Subcon + Project + Node + D/W/M + Date Range
   ✅ Donut chart (#monthly-target) shows:
      - Collected (BLUE) vs Remaining (GREEN)
      - Remaining is computed from NODE TOTAL COLLECTIBLE - Collected
      - Always: Collected >= 0, Remaining >= 0
   NOTE: This is UI/static demo (cycling data for date-range).
   ========================================================= */
(function () {
  // ---------- helpers ----------
  function deepClone(obj) { return JSON.parse(JSON.stringify(obj)); }
  function sumArrays(a, b) {
    var len = Math.max(a.length, b.length);
    var out = new Array(len);
    for (var i = 0; i < len; i++) out[i] = (a[i] || 0) + (b[i] || 0);
    return out;
  }
  function sumArray(arr) {
    var t = 0;
    for (var i = 0; i < (arr || []).length; i++) t += (Number(arr[i]) || 0);
    return t;
  }

  // ---- DATE RANGE HELPERS ----
  function pad2(n) { return (n < 10 ? "0" : "") + n; }
  function toYMD(d) { return d.getFullYear() + "-" + pad2(d.getMonth() + 1) + "-" + pad2(d.getDate()); }
  function parseRange(rangeVal) {
    if (!rangeVal) return { start: null, end: null };
    var parts = rangeVal.split(" to ");
    if (parts.length < 2) return { start: null, end: null };
    return { start: parts[0], end: parts[1] };
  }
  function addDays(d, days) { var x = new Date(d); x.setDate(x.getDate() + days); return x; }
  function startOfWeekMon(d) {
    var x = new Date(d);
    var day = x.getDay();
    var diff = (day === 0 ? -6 : 1 - day);
    x.setDate(x.getDate() + diff);
    x.setHours(0,0,0,0);
    return x;
  }
  function startOfMonth(d) { var x = new Date(d); x.setDate(1); x.setHours(0,0,0,0); return x; }
  function buildCategoriesFromRange(mode, startStr, endStr) {
    var start = new Date(startStr + "T00:00:00");
    var end = new Date(endStr + "T00:00:00");
    if (isNaN(start) || isNaN(end) || start > end) return [];

    var cats = [];
    if (mode === "daily") {
      for (var cur = new Date(start); cur <= end; cur = addDays(cur, 1)) cats.push(toYMD(cur));
      return cats;
    }
    if (mode === "weekly") {
      for (var curW = startOfWeekMon(start); curW <= end; curW = addDays(curW, 7)) cats.push(toYMD(curW));
      return cats;
    }
    for (var curM = startOfMonth(start); curM <= end; curM = new Date(curM.getFullYear(), curM.getMonth() + 1, 1)) {
      cats.push(curM.getFullYear() + "-" + pad2(curM.getMonth() + 1));
    }
    return cats;
  }
  function fitDataToLen(arr, targetLen) {
    var out = [];
    if (!arr || !arr.length || targetLen <= 0) return out;
    for (var i = 0; i < targetLen; i++) out.push(arr[i % arr.length]);
    return out;
  }

  // ---------- DOM ----------
  var barEl = document.querySelector("#crm-project-statistics");
  if (!barEl) return;

  // destroy previous instances
  if (barEl.__apex) { try { barEl.__apex.destroy(); } catch(e){} barEl.__apex = null; }
  var donutEl = document.querySelector("#monthly-target");
  if (donutEl && donutEl.__apex) { try { donutEl.__apex.destroy(); } catch(e){} donutEl.__apex = null; }

  var card = barEl.closest(".card");
  var actions = card ? card.querySelector(".flex.gap-2") : null;
  if (!actions) return;

  var selects = actions.querySelectorAll("select");
  var subconSelect = selects[0] || null;
  var projectSelect = selects[1] || null;
  var nodeSelect = selects[2] || null;
  var btns = actions.querySelectorAll("button"); // D/W/M
  var rangeEl = document.querySelector("#datepicker-range");
  if (!subconSelect || !projectSelect || !nodeSelect) return;

  // ---------- COLORS ----------
  var colorsBar = ["#00704A", "#E11D48"];       // green strand, red hours
  var colorsDonut = ["#3073F1", "#00704A"];     // Collected BLUE, Remaining GREEN

  // =========================================================
  // NODE TOTAL COLLECTIBLE (THIS IS THE "TARGET" NOW)
  // total collectible is stored per node (per project).
  // =========================================================
  var NODE_TARGET = {
    SKY:      { N001: 1200, N002: 800,  N003: 2000 },
    GLOBE:    { N101: 1500, N102: 600,  N103: 2400 },
    PLDT:     { N201: 5000, N202: 2800, N203: 9500 },
    CONVERGE: { N301: 700,  N302: 1200, N303: 400 }
  };

  // =========================================================
  // BAR DATA (Collected + Hours) per (project -> node -> subcon -> mode)
  // NOTE: Static demo values only.
  // =========================================================
  function makeSubconPack(strandDaily, hoursDaily) {
    return {
      daily:  { cats:["Mon","Tue","Wed","Thu","Fri","Sat","Sun"], strand: strandDaily, hours: hoursDaily },
      weekly: { cats:["Week 1","Week 2","Week 3","Week 4","Week 5"], strand: fitDataToLen(strandDaily, 5).map(function(v){return v*5;}), hours: [58,60,61,59,56] },
      monthly:{ cats:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"], strand: fitDataToLen(strandDaily, 12).map(function(v){return v*20;}), hours: [240,245,250,260,255,220,248,242,244,252,250,262] }
    };
  }

  // hours daily must be 8-10
  function H(a,b,c,d,e,f,g){ return [a,b,c,d,e,f,g]; }

  // Create different “behavior” per subcon so obvious changes
  var SUBCON_BASE = {
    JLFT:      makeSubconPack([25,30,20,35,40,18,22], H(8,8,9,9,8,8,8)),      // low
    ICONNECT:  makeSubconPack([90,110,95,120,140,80,85], H(10,9,9,10,9,8,9)),  // high
    MRTEL:     makeSubconPack([55,60,58,70,75,45,48], H(9,9,8,10,9,8,9)),      // mid
    INNOVERGE: makeSubconPack([140,160,150,175,190,120,125], H(9,8,9,10,9,8,8)) // near-finish vibe
  };

  // Build DATA[project][node][subcon] with scaling per node
  var DATA = {};
  Object.keys(NODE_TARGET).forEach(function(project){
    DATA[project] = {};
    Object.keys(NODE_TARGET[project]).forEach(function(node){
      var factor = Math.max(0.4, Math.min(3.0, NODE_TARGET[project][node] / 1200)); // scale by target
      DATA[project][node] = {};
      Object.keys(SUBCON_BASE).forEach(function(sub){
        var base = deepClone(SUBCON_BASE[sub]);
        ["daily","weekly","monthly"].forEach(function(mode){
          base[mode].strand = base[mode].strand.map(function(v){ return Math.round(v * factor); });
          // hours already ok
        });
        DATA[project][node][sub] = base;
      });
    });
  });

  // ---------- state ----------
  var state = {
    subcon: subconSelect.value || "all",
    project: projectSelect.value || "all",
    node: nodeSelect.value || "all",
    mode: "daily",
    range: ""
  };

  // ---------- select → nodes (UI demo) ----------
  function repopulateNodes() {
    var proj = state.project;
    var nodes = [];

    if (proj === "all") {
      // union all nodes (keep short)
      Object.keys(NODE_TARGET).forEach(function(p){
        Object.keys(NODE_TARGET[p]).forEach(function(n){ nodes.push(n); });
      });
    } else {
      nodes = Object.keys(NODE_TARGET[proj] || {});
    }

    // keep current
    var current = state.node;

    // rebuild options (preserve "all")
    var html = '<option value="all" class="bold">Node</option>';
    for (var i = 0; i < nodes.length; i++) html += '<option value="'+nodes[i]+'">'+nodes[i]+'</option>';
    nodeSelect.innerHTML = html;

    // restore if exists, else all
    var exists = false;
    for (var j=0;j<nodeSelect.options.length;j++){
      if (nodeSelect.options[j].value === current) { exists = true; break; }
    }
    nodeSelect.value = exists ? current : "all";
    state.node = nodeSelect.value;
  }

  // ---------- compute pack ----------
  function getBarPack(project, node, subcon, mode) {
    var projects = (project === "all") ? Object.keys(DATA) : [project];
    var cats = null;
    var strand = [];
    var hours = [];

    projects.forEach(function(p){
      var nodesObj = DATA[p] || {};
      var nodes = (node === "all") ? Object.keys(nodesObj) : [node];

      nodes.forEach(function(n){
        var subObj = nodesObj[n];
        if (!subObj) return;

        var subcons = (subcon === "all") ? Object.keys(subObj) : [subcon];
        subcons.forEach(function(s){
          var pack = subObj[s] && subObj[s][mode];
          if (!pack) return;

          if (!cats) cats = pack.cats;
          strand = sumArrays(strand, pack.strand);
          hours = sumArrays(hours, pack.hours);
        });
      });
    });

    if (!cats) return null;
    return {
      cats: cats,
      series: [
        { name: "Strand Collected", data: strand },
        { name: "Working Hours", data: hours }
      ]
    };
  }

  function getNodeTotal(project, node) {
    var projects = (project === "all") ? Object.keys(NODE_TARGET) : [project];
    var total = 0;

    projects.forEach(function(p){
      var nodesObj = NODE_TARGET[p] || {};
      if (node === "all") {
        Object.keys(nodesObj).forEach(function(n){ total += (nodesObj[n] || 0); });
      } else {
        total += (nodesObj[node] || 0);
      }
    });

    return total;
  }

  // ---------- UI: active D/W/M ----------
  function setActive(idx) {
    for (var i = 0; i < btns.length; i++) {
      var b = btns[i];
      if (i === idx) {
        b.classList.remove("bg-gray-400/15","text-gray-600");
        b.classList.add("bg-primary/15","text-primary");
      } else {
        b.classList.remove("bg-primary/15","text-primary");
        b.classList.add("bg-gray-400/15","text-gray-600");
      }
    }
  }

  // ---------- render BAR ----------
  var initBar = getBarPack(state.project, state.node, state.subcon, state.mode);
  if (!initBar) return;

  var barChart = new ApexCharts(barEl, {
    chart: { height: 350, type: "bar", toolbar: { show: false } },
    plotOptions: { bar: { horizontal: false, endingShape: "rounded", columnWidth: "25%" } },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 3, colors: ["transparent"] },
    colors: colorsBar,
    series: initBar.series,
    xaxis: { categories: initBar.cats },
    legend: { offsetY: 7 },
    fill: { opacity: 1 },
    grid: { borderColor: "#9ca3af20" }
  });
  barChart.render();
  barEl.__apex = barChart;

  // ---------- render DONUT ----------
  var donutChart = null;
  if (donutEl) {
    donutChart = new ApexCharts(donutEl, {
      chart: {
        height: 280,
        type: "donut",
        animations: {
          enabled: true,
          easing: "easeinout",
          speed: 900,
          animateGradually: { enabled: true, delay: 120 },
          dynamicAnimation: { enabled: true, speed: 900 }
        }
      },
      legend: { show: false },
      stroke: { colors: ["transparent"] },
      colors: colorsDonut,
      labels: ["Collected", "Remaining"],
      series: [0, 0],
      plotOptions: { pie: { donut: { size: "68%" } } }
    });
    donutChart.render();
    donutEl.__apex = donutChart;
  }

  // ---------- apply BAR + DONUT ----------
  function applyAll() {
    // BAR
    var pack = getBarPack(state.project, state.node, state.subcon, state.mode);
    if (!pack) return;

    var r = parseRange(state.range);
    var cats = pack.cats;
    var series = pack.series;

    if (r.start && r.end) {
      cats = buildCategoriesFromRange(state.mode, r.start, r.end);
      if (!cats.length) return;

      // UI demo: resize both series
      series = pack.series.map(function(s){
        return { name: s.name, data: fitDataToLen(s.data, cats.length) };
      });
    }

    barChart.updateOptions({ xaxis: { categories: cats } }, false, true);
    barChart.updateSeries(series, true);

    // DONUT (NODE TARGET)
    if (donutChart) {
      var totalCollectible = getNodeTotal(state.project, state.node);

      // collected = sum of strand series currently shown (after resize)
      var collected = sumArray(series[0] ? series[0].data : []);
      if (collected < 0) collected = 0;

      // cap collected to total
      if (totalCollectible > 0 && collected > totalCollectible) collected = totalCollectible;

      var remaining = Math.max(0, totalCollectible - collected);

      donutChart.updateSeries([collected, remaining], true);

      // optional: update Pending/Done text blocks if you still use them
      var donutCard = donutEl.closest(".card");
      if (donutCard) {
        var boxes = donutCard.querySelectorAll(".w-1\\/2.text-center");
        if (boxes.length >= 2) {
          var pLeft = boxes[0].querySelector("p");  // left
          var pRight = boxes[1].querySelector("p"); // right
          if (pLeft)  pLeft.innerHTML  = '<i class="mgc_round_fill text-success"></i> ' + remaining + " Remaining";
          if (pRight) pRight.innerHTML = '<i class="mgc_round_fill text-primary"></i> ' + collected + " Collected";
        }
      }
    }
  }

  // ---------- init ----------
  repopulateNodes();
  setActive(0);
  applyAll();

  // ---------- events ----------
  if (btns.length >= 3) {
    btns[0].addEventListener("click", function(){ state.mode = "daily"; setActive(0); applyAll(); });
    btns[1].addEventListener("click", function(){ state.mode = "weekly"; setActive(1); applyAll(); });
    btns[2].addEventListener("click", function(){ state.mode = "monthly"; setActive(2); applyAll(); });
  }

  subconSelect.addEventListener("change", function(e){
    state.subcon = e.target.value || "all";
    applyAll();
  });

  projectSelect.addEventListener("change", function(e){
    state.project = e.target.value || "all";
    repopulateNodes(); // project affects node list
    applyAll();
  });

  nodeSelect.addEventListener("change", function(e){
    state.node = e.target.value || "all";
    applyAll();
  });

  if (rangeEl) {
    rangeEl.addEventListener("change", function(e){
      state.range = e.target.value || "";
      applyAll();
    });
  }
})();