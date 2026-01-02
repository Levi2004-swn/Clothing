(function(){
  'use strict';
  function scorePassword(pw, context){
    if (!pw) return {score:0, suggestions:['Use at least 10 characters']};
    var len = pw.length;
    var classes = 0;
    if (/[a-z]/.test(pw)) classes++;
    if (/[A-Z]/.test(pw)) classes++;
    if (/[0-9]/.test(pw)) classes++;
    if (/[^A-Za-z0-9]/.test(pw)) classes++;

    var score = 0;
    if (len >= 10) score += 2; else if (len >= 8) score += 1;
    score += Math.max(0, classes - 1); // 0..3

    if (/([A-Za-z0-9])\1{2,}/.test(pw)) score = Math.max(0, score - 1);

    if (context && context.hints) {
      for (var i=0;i<context.hints.length;i++){
        var h = (context.hints[i]||'').toLowerCase();
        if (h && h.length >= 3 && pw.toLowerCase().includes(h)) {
          score = Math.max(0, score - 1);
          break;
        }
      }
    }

    var suggestions = [];
    if (len < 10) suggestions.push('Use at least 10 characters');
    if (!/[A-Z]/.test(pw)) suggestions.push('Add an uppercase letter');
    if (!/[a-z]/.test(pw)) suggestions.push('Add a lowercase letter');
    if (!/[0-9]/.test(pw)) suggestions.push('Add a number');
    if (!/[^A-Za-z0-9]/.test(pw)) suggestions.push('Add a symbol');

    return {score: Math.max(0, Math.min(5, score)), suggestions: suggestions};
  }

  function createUI(){
    var wrap = document.createElement('div');
    wrap.className = 'pwd-meter';
    var tip = document.createElement('div');
    tip.className = 'pwd-tip';
    wrap.appendChild(tip);
    return {wrap:wrap, tip:tip};
  }

  function applyScore(tipEl, result){
    var msg = '';
    if (result.score <= 1) msg = 'Very weak';
    else if (result.score === 2) msg = 'Weak';
    else if (result.score === 3) msg = 'Fair';
    else if (result.score === 4) msg = 'Good';
    else msg = 'Strong';
    var sug = result.suggestions.slice(0,2).join(' • ');
    tipEl.textContent = sug ? (msg + ' – ' + sug) : msg;
  }

  function initOne(passwordEl, confirmEl, context){
    if (!passwordEl) return;
    // Prefer placing the checker under the password field
    var container = passwordEl.parentElement;
    var slot = container.querySelector('.pwd-strength');
    var ui = createUI();
    if (slot){
      slot.innerHTML='';
      slot.appendChild(ui.wrap);
    } else {
      passwordEl.insertAdjacentElement('afterend', ui.wrap);
    }

    function refresh(){
      var res = scorePassword(passwordEl.value || '', context);
      applyScore(ui.tip, res);
      if (confirmEl){
        if (confirmEl.value && passwordEl.value && confirmEl.value !== passwordEl.value){
          ui.wrap.classList.add('mismatch');
        } else {
          ui.wrap.classList.remove('mismatch');
        }
      }
    }
    ['keyup','input','change','blur','focus'].forEach(function(ev){
      passwordEl.addEventListener(ev, refresh);
    });
    if (confirmEl){
      confirmEl.addEventListener('input', refresh);
    }
    refresh();
  }

  function collectHints(form){
    var hints = [];
    var email = form && form.querySelector('input[type=email]');
    if (email && email.value) hints.push(email.value.split('@')[0]);
    var fn = form && form.querySelector('input[name="first_name"]');
    var ln = form && form.querySelector('input[name="last_name"]');
    if (fn && fn.value) hints.push(fn.value);
    if (ln && ln.value) hints.push(ln.value);
    return {hints:hints};
  }

  function init(){
    var pairs = [
      {pw:'input[name="password"]', cf:'input[name="confirm_password"]'},
      {pw:'input[name="new_password"]', cf:'input[name="confirm_password"]'},
      {pw:'input[name="new_password"]', cf:null},
    ];
    var seen = new Set();
    pairs.forEach(function(sel){
      var pws = document.querySelectorAll(sel.pw);
      if (!pws) return;
      pws.forEach(function(pw){
        if (seen.has(pw)) return; seen.add(pw);
        // Opt-out mechanism: allow inputs or ancestor containers to disable the checker
        if (pw.hasAttribute('data-no-strength') || (pw.closest('[data-no-pw-strength]'))) return;
        var form = pw.closest('form');
        var cf = sel.cf ? (form ? form.querySelector(sel.cf) : document.querySelector(sel.cf)) : null;
        initOne(pw, cf, collectHints(form));
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
