/**
 * SokratCRM Unified Custom Dropdown Engine
 * Automatically upgrades <select class="crm-custom-select"> or <select data-crm-dropdown>
 * into a fully themed, accessible, searchable, custom dropdown.
 */

(function () {
  'use strict';

  const SVG_CHEVRON = `<svg class="crm-dropdown-chevron" width="11" height="11" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>`;
  const SVG_CHECK = `<svg class="crm-dropdown-check" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/></svg>`;
  const SVG_SEARCH = `<svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>`;

  function buildCustomDropdown(select) {
    if (select.__crmDropdownActive) return;
    select.__crmDropdownActive = true;

    // Extract leading icon if specified
    const iconSvg = select.dataset.icon || '';
    const searchable = select.options.length > 5;
    const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
    const searchPlaceholder = isRtl ? 'بحث...' : 'Search...';
    const noResultsText = isRtl ? 'لا توجد نتائج مطابقة' : 'No matching results';

    // Create wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'crm-dropdown';
    if (select.className) {
      wrapper.className += ' ' + select.className.replace(/crm-custom-select/g, '').trim();
    }
    if (select.id) {
      wrapper.dataset.selectId = select.id;
    }

    // Hide original select visually but keep it in DOM for forms/submits
    select.style.position = 'absolute';
    select.style.opacity = '0';
    select.style.pointerEvents = 'none';
    select.style.width = '0';
    select.style.height = '0';
    select.tabIndex = -1;

    // Determine currently selected option
    const selectedOption = select.options[select.selectedIndex] || select.options[0];
    const initialText = selectedOption ? selectedOption.textContent.trim() : '';

    // Create Trigger
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'crm-dropdown-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    let triggerHtml = '';
    if (iconSvg) {
      triggerHtml += `<span class="crm-dropdown-leading-icon">${iconSvg}</span>`;
    }
    triggerHtml += `<span class="crm-dropdown-text">${escapeHtml(initialText)}</span>`;
    triggerHtml += SVG_CHEVRON;
    trigger.innerHTML = triggerHtml;

    // Create Dropdown Menu
    const menu = document.createElement('div');
    menu.className = 'crm-dropdown-menu';
    menu.setAttribute('role', 'listbox');

    let searchInput = null;
    if (searchable) {
      const searchWrap = document.createElement('div');
      searchWrap.className = 'crm-dropdown-search-wrap';
      searchWrap.innerHTML = `<span class="crm-dropdown-search-icon">${SVG_SEARCH}</span>`;

      searchInput = document.createElement('input');
      searchInput.type = 'text';
      searchInput.className = 'crm-dropdown-search-input';
      searchInput.placeholder = searchPlaceholder;
      searchInput.autocomplete = 'off';

      searchWrap.appendChild(searchInput);
      menu.appendChild(searchWrap);
    }

    const list = document.createElement('ul');
    list.className = 'crm-dropdown-list';

    // Build items
    Array.from(select.options).forEach((opt) => {
      const item = document.createElement('li');
      item.className = 'crm-dropdown-item';
      item.setAttribute('role', 'option');
      item.dataset.value = opt.value;
      if (opt.selected) {
        item.classList.add('is-selected');
        item.setAttribute('aria-selected', 'true');
      }

      item.innerHTML = `<span class="crm-dropdown-item-text">${escapeHtml(opt.textContent.trim())}</span>${SVG_CHECK}`;

      item.addEventListener('click', () => {
        selectItem(opt.value, item.textContent.trim());
      });

      list.appendChild(item);
    });

    menu.appendChild(list);

    // Insert wrapper into DOM
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    wrapper.appendChild(trigger);
    wrapper.appendChild(menu);

    // State management
    function openMenu() {
      // Close any other open dropdowns
      document.querySelectorAll('.crm-dropdown.is-open').forEach((d) => {
        if (d !== wrapper) d.classList.remove('is-open');
      });

      wrapper.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');

      if (searchInput) {
        searchInput.value = '';
        filterItems('');
        setTimeout(() => searchInput.focus(), 30);
      } else {
        trigger.focus();
      }

      // Scroll selected item into view
      const selectedItem = list.querySelector('.is-selected');
      if (selectedItem) {
        selectedItem.scrollIntoView({ block: 'nearest' });
      }
    }

    function closeMenu() {
      wrapper.classList.remove('is-open');
      trigger.setAttribute('aria-expanded', 'false');
    }

    function toggleMenu() {
      if (wrapper.classList.contains('is-open')) {
        closeMenu();
      } else {
        openMenu();
      }
    }

    function selectItem(value, labelText) {
      // Update original select value
      if (select.value !== value) {
        select.value = value;
        // Trigger native change event (and form submit if attached)
        select.dispatchEvent(new Event('change', { bubbles: true }));
      }

      // Update trigger label
      const textEl = trigger.querySelector('.crm-dropdown-text');
      if (textEl) textEl.textContent = labelText;

      // Update selected class
      list.querySelectorAll('.crm-dropdown-item').forEach((it) => {
        const isMatch = it.dataset.value === value;
        it.classList.toggle('is-selected', isMatch);
        it.setAttribute('aria-selected', isMatch ? 'true' : 'false');
      });

      closeMenu();
      trigger.focus();
    }

    function filterItems(query) {
      const q = query.trim().toLowerCase();
      let matches = 0;

      list.querySelectorAll('.crm-dropdown-item').forEach((item) => {
        const text = item.textContent.toLowerCase();
        const visible = text.includes(q);
        item.style.display = visible ? '' : 'none';
        if (visible) matches++;
      });

      let noRes = list.querySelector('.crm-dropdown-no-results');
      if (matches === 0) {
        if (!noRes) {
          noRes = document.createElement('li');
          noRes.className = 'crm-dropdown-no-results';
          noRes.textContent = noResultsText;
          list.appendChild(noRes);
        }
      } else if (noRes) {
        noRes.remove();
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', (e) => filterItems(e.target.value));
      searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeMenu();
          trigger.focus();
        } else if (e.key === 'ArrowDown') {
          e.preventDefault();
          const firstVisible = Array.from(list.querySelectorAll('.crm-dropdown-item')).find(
            (it) => it.style.display !== 'none'
          );
          if (firstVisible) firstVisible.focus();
        }
      });
    }

    // Trigger click
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      toggleMenu();
    });

    // Keyboard support on trigger
    trigger.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openMenu();
      } else if (e.key === 'Escape') {
        closeMenu();
      }
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
      if (!wrapper.contains(e.target)) {
        closeMenu();
      }
    });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function initAllDropdowns(root = document) {
    const selector = 'select.crm-custom-select, select[data-crm-dropdown]';
    root.querySelectorAll(selector).forEach(buildCustomDropdown);
  }

  // Auto-init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initAllDropdowns());
  } else {
    initAllDropdowns();
  }

  // Auto-init on dynamic insertion
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          if (node.matches && node.matches('select.crm-custom-select, select[data-crm-dropdown]')) {
            buildCustomDropdown(node);
          } else if (node.querySelectorAll) {
            initAllDropdowns(node);
          }
        }
      });
    });
  });
  observer.observe(document.documentElement, { childList: true, subtree: true });

  window.initCrmDropdowns = initAllDropdowns;
})();
