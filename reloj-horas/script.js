(function () {
  "use strict";

  var CX = 150, CY = 150;
  var STORAGE_KEY = "relojHorasScore";

  var MINUTE_STEP = { facil: 60, medio: 15, avanzado: 5, experto: 1 };
  var MINUTE_CHOICES = {
    facil: [0],
    medio: [0, 15, 30, 45],
    avanzado: [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55],
    experto: null // cualquier minuto 0-59
  };

  var svg = document.getElementById("clockSvg");
  var ticksGroup = document.getElementById("ticksGroup");
  var numbersGroup = document.getElementById("numbersGroup");
  var hourHandGroup = document.getElementById("hourHandGroup");
  var minuteHandGroup = document.getElementById("minuteHandGroup");

  var modeLeerBtn = document.getElementById("modeLeerBtn");
  var modePonerBtn = document.getElementById("modePonerBtn");
  var difficultySelect = document.getElementById("difficultySelect");
  var scoreDisplay = document.getElementById("scoreDisplay");

  var leerPanel = document.getElementById("leerPanel");
  var ponerPanel = document.getElementById("ponerPanel");
  var hourSelect = document.getElementById("hourSelect");
  var minuteSelect = document.getElementById("minuteSelect");
  var targetTimeText = document.getElementById("targetTimeText");

  var checkBtn = document.getElementById("checkBtn");
  var nextBtn = document.getElementById("nextBtn");
  var feedback = document.getElementById("feedback");
  var resetScoreBtn = document.getElementById("resetScoreBtn");

  var state = {
    mode: "leer",
    difficulty: "medio",
    target: { hour: 12, minute: 0 },
    current: { hour: 12, minute: 0 },
    dragging: null,
    answered: false,
    score: { correct: 0, total: 0 }
  };

  function pad(n) { return n < 10 ? "0" + n : "" + n; }
  function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
  function choice(arr) { return arr[randInt(0, arr.length - 1)]; }

  function polarPoint(radius, angleDeg) {
    var rad = (angleDeg * Math.PI) / 180;
    return { x: CX + radius * Math.sin(rad), y: CY - radius * Math.cos(rad) };
  }

  function buildFace() {
    for (var k = 0; k < 60; k++) {
      var deg = k * 6;
      var isMajor = k % 5 === 0;
      var outer = polarPoint(145, deg);
      var inner = polarPoint(isMajor ? 122 : 133, deg);
      var line = document.createElementNS(svg.namespaceURI, "line");
      line.setAttribute("x1", inner.x);
      line.setAttribute("y1", inner.y);
      line.setAttribute("x2", outer.x);
      line.setAttribute("y2", outer.y);
      line.setAttribute("class", isMajor ? "tick-major" : "tick-minor");
      ticksGroup.appendChild(line);
    }

    for (var i = 1; i <= 12; i++) {
      var pos = polarPoint(105, i * 30);
      var text = document.createElementNS(svg.namespaceURI, "text");
      text.setAttribute("x", pos.x);
      text.setAttribute("y", pos.y);
      text.setAttribute("class", "clock-number");
      text.textContent = i;
      numbersGroup.appendChild(text);
    }
  }

  function populateSelects() {
    var blank = document.createElement("option");
    blank.value = "";
    blank.textContent = "--";
    hourSelect.appendChild(blank);
    for (var h = 1; h <= 12; h++) {
      var opt = document.createElement("option");
      opt.value = h;
      opt.textContent = h;
      hourSelect.appendChild(opt);
    }

    var blankM = document.createElement("option");
    blankM.value = "";
    blankM.textContent = "--";
    minuteSelect.appendChild(blankM);
    for (var m = 0; m < 60; m++) {
      var optM = document.createElement("option");
      optM.value = m;
      optM.textContent = pad(m);
      minuteSelect.appendChild(optM);
    }
  }

  function renderClock(hour, minute, geared) {
    var hourAngle = geared ? ((hour % 12) + minute / 60) * 30 : (hour % 12) * 30;
    var minuteAngle = minute * 6;
    hourHandGroup.setAttribute("transform", "rotate(" + hourAngle + " " + CX + " " + CY + ")");
    minuteHandGroup.setAttribute("transform", "rotate(" + minuteAngle + " " + CX + " " + CY + ")");
  }

  function randomMinuteForDifficulty(difficulty) {
    var choices = MINUTE_CHOICES[difficulty];
    if (choices) return choice(choices);
    return randInt(0, 59);
  }

  function setHandsInteractive(enabled) {
    var hitAreas = document.querySelectorAll(".hit-area");
    hitAreas.forEach(function (el) {
      el.classList.toggle("disabled", !enabled);
    });
  }

  function updateScoreDisplay() {
    scoreDisplay.textContent = "Aciertos: " + state.score.correct + " / " + state.score.total;
  }

  function saveScore() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state.score));
    } catch (e) { /* almacenamiento no disponible */ }
  }

  function loadScore() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        var parsed = JSON.parse(raw);
        if (typeof parsed.correct === "number" && typeof parsed.total === "number") {
          state.score = parsed;
        }
      }
    } catch (e) { /* ignorar */ }
  }

  function newRound() {
    state.answered = false;
    state.target.hour = randInt(1, 12);
    state.target.minute = randomMinuteForDifficulty(state.difficulty);

    feedback.textContent = "";
    feedback.className = "feedback";
    checkBtn.classList.remove("hidden");
    nextBtn.classList.add("hidden");

    if (state.mode === "leer") {
      renderClock(state.target.hour, state.target.minute, true);
      hourSelect.value = "";
      minuteSelect.value = "";
      hourSelect.disabled = false;
      minuteSelect.disabled = false;
      setHandsInteractive(false);
    } else {
      state.current.hour = 12;
      state.current.minute = 0;
      renderClock(state.current.hour, state.current.minute, false);
      targetTimeText.textContent = state.target.hour + ":" + pad(state.target.minute);
      setHandsInteractive(true);
    }
  }

  function setMode(mode) {
    state.mode = mode;
    modeLeerBtn.classList.toggle("active", mode === "leer");
    modeLeerBtn.setAttribute("aria-selected", mode === "leer");
    modePonerBtn.classList.toggle("active", mode === "poner");
    modePonerBtn.setAttribute("aria-selected", mode === "poner");
    leerPanel.classList.toggle("hidden", mode !== "leer");
    ponerPanel.classList.toggle("hidden", mode !== "poner");
    newRound();
  }

  function checkAnswer() {
    var correct;
    if (state.mode === "leer") {
      var h = parseInt(hourSelect.value, 10);
      var m = parseInt(minuteSelect.value, 10);
      if (isNaN(h) || isNaN(m)) {
        feedback.textContent = "Selecciona una hora y unos minutos.";
        feedback.className = "feedback";
        return;
      }
      correct = h === state.target.hour && m === state.target.minute;
      hourSelect.disabled = true;
      minuteSelect.disabled = true;
    } else {
      correct = state.current.hour === state.target.hour && state.current.minute === state.target.minute;
      setHandsInteractive(false);
      if (!correct) {
        renderClock(state.target.hour, state.target.minute, false);
      }
    }

    state.score.total++;
    if (correct) state.score.correct++;
    updateScoreDisplay();
    saveScore();

    var targetText = state.target.hour + ":" + pad(state.target.minute);
    if (correct) {
      feedback.textContent = "¡Correcto! 🎉";
      feedback.className = "feedback ok";
    } else {
      feedback.textContent = "Casi... la hora correcta era las " + targetText + ".";
      feedback.className = "feedback fail";
    }

    state.answered = true;
    checkBtn.classList.add("hidden");
    nextBtn.classList.remove("hidden");
  }

  function getSvgPoint(evt) {
    var pt = svg.createSVGPoint();
    pt.x = evt.clientX;
    pt.y = evt.clientY;
    var ctm = svg.getScreenCTM();
    return pt.matrixTransform(ctm.inverse());
  }

  function angleFromEvent(evt) {
    var pt = getSvgPoint(evt);
    var dx = pt.x - CX;
    var dy = pt.y - CY;
    var angle = Math.atan2(dx, -dy) * (180 / Math.PI);
    if (angle < 0) angle += 360;
    return angle;
  }

  function handleDrag(evt) {
    var angle = angleFromEvent(evt);
    if (state.dragging === "minute") {
      var step = MINUTE_STEP[state.difficulty];
      var rawMinute = angle / 6;
      var snapped = Math.round(rawMinute / step) * step;
      snapped = ((snapped % 60) + 60) % 60;
      state.current.minute = snapped;
    } else if (state.dragging === "hour") {
      var rawHour = angle / 30;
      var snappedHour = Math.round(rawHour) % 12;
      if (snappedHour === 0) snappedHour = 12;
      state.current.hour = snappedHour;
    }
    renderClock(state.current.hour, state.current.minute, false);
  }

  function startDrag(handType) {
    return function (evt) {
      if (state.mode !== "poner" || state.answered) return;
      evt.preventDefault();
      state.dragging = handType;
      hourHandGroup.classList.add("no-transition");
      minuteHandGroup.classList.add("no-transition");
      try { svg.setPointerCapture(evt.pointerId); } catch (e) { /* ignorar */ }
      handleDrag(evt);
    };
  }

  function endDrag() {
    state.dragging = null;
    hourHandGroup.classList.remove("no-transition");
    minuteHandGroup.classList.remove("no-transition");
  }

  function init() {
    buildFace();
    populateSelects();
    loadScore();
    updateScoreDisplay();
    renderClock(12, 0, true);

    modeLeerBtn.addEventListener("click", function () { setMode("leer"); });
    modePonerBtn.addEventListener("click", function () { setMode("poner"); });
    difficultySelect.addEventListener("change", function () {
      state.difficulty = difficultySelect.value;
      newRound();
    });
    checkBtn.addEventListener("click", checkAnswer);
    nextBtn.addEventListener("click", newRound);
    resetScoreBtn.addEventListener("click", function () {
      state.score = { correct: 0, total: 0 };
      updateScoreDisplay();
      saveScore();
    });

    hourHandGroup.querySelector(".hit-area").addEventListener("pointerdown", startDrag("hour"));
    minuteHandGroup.querySelector(".hit-area").addEventListener("pointerdown", startDrag("minute"));
    svg.addEventListener("pointermove", function (evt) {
      if (state.dragging) handleDrag(evt);
    });
    svg.addEventListener("pointerup", endDrag);
    svg.addEventListener("pointercancel", endDrag);

    newRound();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
