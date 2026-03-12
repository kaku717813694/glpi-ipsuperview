(function () {
  var isChinese = (document.documentElement.lang || '').toLowerCase().indexOf('zh') === 0;
  var messages = {
    waiting: isChinese ? '待输入' : 'Waiting',
    invalid: isChinese ? '格式错误' : 'Invalid format',
    previewHint: isChinese
      ? '输入网段后，这里会自动显示标准化结果、可用范围和主机数。'
      : 'After you enter a subnet, the normalized CIDR, usable range, and host count will appear here.',
    invalidHint: isChinese
      ? 'CIDR 必须是 IPv4，并且前缀长度在 /20 到 /30 之间。'
      : 'CIDR must be IPv4 and the prefix length must be between /20 and /30.',
    recognizedHint: isChinese
      ? '已识别这个网段，下面是自动计算出的可用范围。'
      : 'Subnet recognized. The usable range below was calculated automatically.'
  };

  function ipToNumber(ip) {
    var parts = ip.split('.').map(function (part) { return Number(part); });
    if (parts.length !== 4 || parts.some(function (part) { return !Number.isInteger(part) || part < 0 || part > 255; })) {
      return null;
    }

    return (((parts[0] * 256 + parts[1]) * 256 + parts[2]) * 256 + parts[3]) >>> 0;
  }

  function numberToIp(value) {
    return [
      (value >>> 24) & 255,
      (value >>> 16) & 255,
      (value >>> 8) & 255,
      value & 255
    ].join('.');
  }

  function parseCidr(value) {
    var match = /^\s*(\d{1,3}(?:\.\d{1,3}){3})\/(\d{1,2})\s*$/.exec(value);
    if (!match) {
      return null;
    }

    var ip = match[1];
    var prefix = Number(match[2]);
    var base = ipToNumber(ip);
    if (base === null || prefix < 20 || prefix > 30) {
      return null;
    }

    var mask = prefix === 0 ? 0 : ((0xffffffff << (32 - prefix)) >>> 0);
    var network = (base & mask) >>> 0;
    var broadcast = (network | (~mask >>> 0)) >>> 0;
    var first = (network + 1) >>> 0;
    var last = (broadcast - 1) >>> 0;
    var hostCount = last >= first ? (last - first + 1) : 0;
    if (hostCount <= 0) {
      return null;
    }

    return {
      cidr: numberToIp(network) + '/' + prefix,
      firstIp: numberToIp(first),
      lastIp: numberToIp(last),
      hostCount: hostCount
    };
  }

  function bindPreview() {
    var cidrInput = document.querySelector('input[name="cidr"]');
    var statusEl = document.querySelector('[data-ipsuperview-preview-status]');
    var cidrPreview = document.querySelector('[data-ipsuperview-preview-cidr]');
    var firstPreview = document.querySelector('[data-ipsuperview-preview-first]');
    var lastPreview = document.querySelector('[data-ipsuperview-preview-last]');
    var hostsPreview = document.querySelector('[data-ipsuperview-preview-hosts]');
    if (!cidrInput || !statusEl || !cidrPreview || !firstPreview || !lastPreview || !hostsPreview) {
      return false;
    }

    if (cidrInput.dataset.ipsuperviewBound === '1') {
      return true;
    }
    cidrInput.dataset.ipsuperviewBound = '1';

    var setPreviewValues = function (values) {
      cidrPreview.textContent = values.cidr;
      firstPreview.textContent = values.first;
      lastPreview.textContent = values.last;
      hostsPreview.textContent = values.hosts;
    };

    var update = function (normalizeInput) {
      var value = cidrInput.value.trim();
      if (value === '') {
        setPreviewValues({
          cidr: messages.waiting,
          first: messages.waiting,
          last: messages.waiting,
          hosts: '0'
        });
        statusEl.textContent = messages.previewHint;
        statusEl.classList.remove('ipsuperview-formhint--error');
        statusEl.classList.add('ipsuperview-formhint--muted');
        return;
      }

      var parsed = parseCidr(cidrInput.value);
      if (!parsed) {
        setPreviewValues({
          cidr: messages.invalid,
          first: '-',
          last: '-',
          hosts: '0'
        });
        statusEl.textContent = messages.invalidHint;
        statusEl.classList.add('ipsuperview-formhint--error');
        statusEl.classList.remove('ipsuperview-formhint--muted');
        return;
      }

      if (normalizeInput) {
        cidrInput.value = parsed.cidr;
      }

      setPreviewValues({
        cidr: parsed.cidr,
        first: parsed.firstIp,
        last: parsed.lastIp,
        hosts: String(parsed.hostCount)
      });
      statusEl.textContent = messages.recognizedHint;
      statusEl.classList.remove('ipsuperview-formhint--error');
      statusEl.classList.remove('ipsuperview-formhint--muted');
    };

    cidrInput.addEventListener('input', function () {
      update(false);
    });
    cidrInput.addEventListener('blur', function () {
      update(true);
    });
    update(false);
    return true;
  }

  function bindTableFilters() {
    var tableNames = {};
    document.querySelectorAll('[data-ipsuperview-filter]').forEach(function (input) {
      tableNames[input.getAttribute('data-ipsuperview-filter')] = true;
    });
    document.querySelectorAll('[data-ipsuperview-quickfilter]').forEach(function (button) {
      tableNames[button.getAttribute('data-ipsuperview-quickfilter')] = true;
    });

    var names = Object.keys(tableNames);
    if (!names.length) {
      return false;
    }

    names.forEach(function (tableName) {
      var table = document.querySelector('[data-ipsuperview-table="' + tableName + '"]');
      if (!table || table.dataset.ipsuperviewBound === '1') {
        return;
      }

      table.dataset.ipsuperviewBound = '1';
      var input = document.querySelector('[data-ipsuperview-filter="' + tableName + '"]');
      if (input && input.dataset.ipsuperviewBound !== '1') {
        input.dataset.ipsuperviewBound = '1';
      }

      var rows = Array.prototype.slice.call(table.querySelectorAll('tr[data-ipsuperview-row]'));
      var quickButtons = Array.prototype.slice.call(
        document.querySelectorAll('[data-ipsuperview-quickfilter="' + tableName + '"]')
      );
      var activeButton = quickButtons.find(function (button) {
        return button.classList.contains('is-active');
      });
      var activeMode = activeButton ? (activeButton.getAttribute('data-ipsuperview-mode') || 'all').toLowerCase() : 'all';

      var matchesMode = function (row) {
        var freshness = (row.getAttribute('data-ipsuperview-freshness') || '').toLowerCase();
        var status = (row.getAttribute('data-ipsuperview-status') || '').toLowerCase();
        if (activeMode === 'stale') {
          return freshness === 'stale' || freshness === 'aged';
        }
        if (activeMode === 'aged') {
          return freshness === 'aged';
        }
        if (activeMode === 'free') {
          return status === 'free';
        }
        if (activeMode === 'used') {
          return status === 'assigned' || status === 'duplicate';
        }
        return true;
      };

      var applyFilter = function () {
        var keyword = input ? input.value.trim().toLowerCase() : '';
        rows.forEach(function (row) {
          var haystack = row.getAttribute('data-ipsuperview-row') || '';
          var visible = (keyword === '' || haystack.indexOf(keyword) !== -1) && matchesMode(row);
          row.style.display = visible ? '' : 'none';
        });
      };

      if (input) {
        input.addEventListener('input', applyFilter);
      }
      quickButtons.forEach(function (button) {
        if (button.dataset.ipsuperviewBound === '1') {
          return;
        }
        button.dataset.ipsuperviewBound = '1';
        button.addEventListener('click', function () {
          activeMode = (button.getAttribute('data-ipsuperview-mode') || 'all').toLowerCase();
          quickButtons.forEach(function (item) {
            item.classList.toggle('is-active', item === button);
          });
          applyFilter();
        });
      });
      applyFilter();
    });

    return true;
  }

  function bindOpenDetailsButtons() {
    var buttons = document.querySelectorAll('[data-ipsuperview-open-details]');
    if (!buttons.length) {
      return false;
    }

    buttons.forEach(function (button) {
      if (button.dataset.ipsuperviewBound === '1') {
        return;
      }
      button.dataset.ipsuperviewBound = '1';
      button.addEventListener('click', function () {
        var tableName = button.getAttribute('data-ipsuperview-open-details');
        var mode = (button.getAttribute('data-ipsuperview-open-mode') || 'all').toLowerCase();
        var details = document.querySelector('[data-ipsuperview-details="' + tableName + '"]');
        var detailsPanel = document.querySelector('[data-ipsuperview-workspace-panel="details"]');
        var workspaceTab = document.querySelector('[data-ipsuperview-workspace-target="details"]');
        var targetButton = document.querySelector(
          '[data-ipsuperview-quickfilter="' + tableName + '"][data-ipsuperview-mode="' + mode + '"]'
        );

        if (workspaceTab) {
          workspaceTab.click();
        } else if (detailsPanel) {
          detailsPanel.hidden = false;
        }
        if (details) {
          details.open = true;
          details.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        if (targetButton) {
          window.setTimeout(function () {
            targetButton.click();
          }, 60);
        }
      });
    });

    return true;
  }

  function bindWorkspaceTabs() {
    var navs = document.querySelectorAll('[data-ipsuperview-workspace]');
    if (!navs.length) {
      return false;
    }

    navs.forEach(function (nav) {
      if (nav.dataset.ipsuperviewBound === '1') {
        return;
      }
      nav.dataset.ipsuperviewBound = '1';

      var tabs = Array.prototype.slice.call(nav.querySelectorAll('[data-ipsuperview-workspace-target]'));
      var panels = Array.prototype.slice.call(document.querySelectorAll('[data-ipsuperview-workspace-panel]'));

      var applyMode = function (mode) {
        tabs.forEach(function (tab) {
          var active = (tab.getAttribute('data-ipsuperview-workspace-target') || '') === mode;
          tab.classList.toggle('is-active', active);
          tab.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
          var key = panel.getAttribute('data-ipsuperview-workspace-panel') || '';
          panel.hidden = !(mode === 'all' || key === mode);
        });
      };

      var defaultTab = tabs.find(function (tab) {
        return tab.classList.contains('is-active');
      });
      applyMode(defaultTab ? defaultTab.getAttribute('data-ipsuperview-workspace-target') : 'assigned');

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          var mode = (tab.getAttribute('data-ipsuperview-workspace-target') || 'assigned').toLowerCase();
          applyMode(mode);
          if (mode === 'details') {
            var details = document.querySelector('[data-ipsuperview-details="details-table"]');
            var freeButton = document.querySelector('[data-ipsuperview-quickfilter="details-table"][data-ipsuperview-mode="free"]');
            if (details) {
              details.open = true;
            }
            if (freeButton) {
              window.setTimeout(function () {
                freeButton.click();
              }, 60);
            }
          }
          var targetPanel = document.querySelector('[data-ipsuperview-workspace-panel="' + mode + '"]');
          if (targetPanel) {
            targetPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
          } else {
            nav.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      });
    });

    return true;
  }

  function bootPreview() {
    bindPreview();
    bindTableFilters();
    bindOpenDetailsButtons();
    bindWorkspaceTabs();

    if (window.__ipsuperviewObserverStarted) {
      return;
    }
    window.__ipsuperviewObserverStarted = true;

    var scheduleBind = function () {
      bindPreview();
      bindTableFilters();
      bindOpenDetailsButtons();
      bindWorkspaceTabs();
    };

    var observer = new MutationObserver(function () {
      scheduleBind();
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });

    document.addEventListener('click', function () {
      window.setTimeout(scheduleBind, 50);
      window.setTimeout(scheduleBind, 250);
      window.setTimeout(scheduleBind, 800);
    }, true);

    window.setInterval(scheduleBind, 2000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPreview);
  } else {
    bootPreview();
  }
})();
