(() => {
 if (window.__sokratCrmSidebarReady) {
  return;
 }
 window.__sokratCrmSidebarReady = true;

 const storageKey = 'sokrat.crm.sidebar.collapsed';
 const root = document.documentElement;
 const body = document.body || document.querySelector('body');
 const sidebar = document.getElementById('crmSidebar');
 const collapseButton = document.getElementById('crmSidebarCollapseBtn');

 const canStore = () => {
  try {
   localStorage.setItem('__sokrat_sidebar_test__', '1');
   localStorage.removeItem('__sokrat_sidebar_test__');
   return true;
  } catch (error) {
   return false;
  }
 };

 const storageAvailable = canStore();

 const readCollapsed = () => {
  if (!storageAvailable) {
   return root.classList.contains('crm-sidebar-collapsed');
  }

  return localStorage.getItem(storageKey) === '1';
 };

 const writeCollapsed = (collapsed) => {
  if (!storageAvailable) {
   return;
  }

  localStorage.setItem(storageKey, collapsed ? '1' : '0');
 };

 const setTitleFromLabel = (element) => {
  if (!element || element.hasAttribute('title')) {
   return;
  }

  const label = element.querySelector('.crm-label, .label');
  const text = label ? label.textContent.trim().replace(/\s+/g, ' ') : '';

  if (text) {
   element.setAttribute('title', text);
  }
 };

 const updateCollapseButton = (collapsed) => {
  if (!collapseButton) {
   return;
  }

  const icon = collapseButton.querySelector('i');
  const label = collapseButton.querySelector('span');
  const title = collapsed ? 'توسيع القائمة الجانبية' : 'طي القائمة الجانبية';

  collapseButton.setAttribute('aria-label', title);
  collapseButton.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
  collapseButton.setAttribute('title', title);

  if (label) {
   label.textContent = collapsed ? 'توسيع القائمة' : 'طي القائمة';
  }

  if (icon) {
   icon.className = collapsed
    ? 'bi bi-chevron-double-left'
    : 'bi bi-chevron-double-right';
  }
 };

 const setCollapsed = (collapsed, persist = true) => {
  root.classList.toggle('crm-sidebar-collapsed', collapsed);
  if (body) {
   body.classList.toggle('crm-sidebar-collapsed', collapsed);
  }

  updateCollapseButton(collapsed);

  if (persist) {
   writeCollapsed(collapsed);
  }
 };

 const isCollapsed = () => root.classList.contains('crm-sidebar-collapsed');

 let animationTimer = null;

 const toggleCollapsedAnimated = (targetState) => {
  if (animationTimer) {
   clearTimeout(animationTimer);
   animationTimer = null;
  }

  const collapsing = targetState !== undefined ? targetState : !isCollapsed();

  if (collapsing === isCollapsed()) {
   return;
  }
  if (collapsing) {
   root.classList.add('crm-sidebar-animating', 'crm-sidebar-fading-out');
   setCollapsed(true);
   setTimeout(() => {
    root.classList.remove('crm-sidebar-fading-out');
   }, 90);
   animationTimer = setTimeout(() => {
    root.classList.remove('crm-sidebar-animating');
    animationTimer = null;
   }, 220);
  } else {
   root.classList.add('crm-sidebar-animating', 'crm-sidebar-fading-in');
   setCollapsed(false);

   animationTimer = setTimeout(() => {
    root.classList.remove('crm-sidebar-animating', 'crm-sidebar-fading-in');
    animationTimer = null;
   }, 220);
  }
 };

 const setMenuOpen = (button, target, open) => {
  target.classList.toggle('open', open);
  button.setAttribute('aria-expanded', open ? 'true' : 'false');
 };

 document.querySelectorAll('.crm-link, .crm-toggle').forEach(setTitleFromLabel);
 setCollapsed(readCollapsed(), false);

 if (collapseButton) {
  collapseButton.addEventListener('click', () => {
   toggleCollapsedAnimated();
  });
 }

 if (sidebar) {
  sidebar.addEventListener(
   'click',
   (event) => {
    const button = event.target.closest('[data-crm-menu]');

    if (button && sidebar.contains(button)) {
     const menuId = button.dataset.crmMenu || button.dataset.menu;
     const target = menuId ? document.getElementById(menuId) : null;

     if (target) {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();

      if (isCollapsed() && window.matchMedia('(min-width: 901px)').matches) {
       toggleCollapsedAnimated(false);
       setMenuOpen(button, target, true);
       return;
      }

      setMenuOpen(button, target, !target.classList.contains('open'));
      return;
     }
    }

    // Close mobile drawer when clicking navigation link on mobile (<900px)
    const link = event.target.closest('a[href]');
    if (link && sidebar.contains(link)) {
     if (window.matchMedia('(max-width: 900px)').matches) {
      closeMobileDrawer();
     }
    }
   },
   true
  );
 }

 /* ==========================================================================
    STANDARDIZED MOBILE SIDEBAR DRAWER STATE MACHINE
    ========================================================================== */

 const MENU_BTN_SELECTOR = '#menu, #crmMenuButton, .crm-topbar-menu-btn, .dash-menu-toggle, .menu-button, .support-menu, .report-menu, [data-drawer-toggle]';
 const OVERLAY_SELECTOR = '#crmSidebarOverlay, .crm-overlay, .overlay, #overlay, #supportSidebarOverlay';

 const ensureOverlay = () => {
  let overlay = document.querySelector(OVERLAY_SELECTOR);
  if (!overlay) {
   overlay = document.createElement('button');
   overlay.id = 'crmSidebarOverlay';
   overlay.type = 'button';
   overlay.className = 'crm-overlay overlay';
   overlay.setAttribute('aria-label', 'Close Menu');
   (document.body || body || root).appendChild(overlay);
  }
  return overlay;
 };

 const isMobileDrawerOpen = () => {
  const targetBody = document.body || body;
  if (!targetBody) return false;
  return targetBody.classList.contains('crm-side-open') ||
         targetBody.classList.contains('side-open') ||
         targetBody.classList.contains('transfer-side-open');
 };

 const openMobileDrawer = () => {
  ensureOverlay();
  const targetBody = document.body || body;
  if (targetBody) {
   targetBody.classList.add('crm-side-open', 'side-open', 'transfer-side-open');
  }
  root.classList.add('crm-side-open', 'side-open', 'transfer-side-open');

  document.querySelectorAll(MENU_BTN_SELECTOR).forEach((btn) => {
   btn.setAttribute('aria-expanded', 'true');
  });
 };

 const closeMobileDrawer = () => {
  if (!isMobileDrawerOpen()) {
   return;
  }
  const targetBody = document.body || body;
  if (targetBody) {
   targetBody.classList.remove('crm-side-open', 'side-open', 'transfer-side-open');
  }
  root.classList.remove('crm-side-open', 'side-open', 'transfer-side-open');

  document.querySelectorAll(MENU_BTN_SELECTOR).forEach((btn) => {
   btn.setAttribute('aria-expanded', 'false');
  });
 };

 const toggleMobileDrawer = (event) => {
  if (event) {
   event.preventDefault();
   event.stopPropagation();
  }
  if (isMobileDrawerOpen()) {
   closeMobileDrawer();
  } else {
   openMobileDrawer();
  }
 };

 // Export global functions for external callers
 window.sokratToggleSidebar = toggleMobileDrawer;
 window.sokratOpenSidebar = openMobileDrawer;
 window.sokratCloseSidebar = closeMobileDrawer;
 window.sokratIsSidebarOpen = isMobileDrawerOpen;

 // 1. Global Delegated Click Handler for Menu Toggle & Overlay
 document.addEventListener('click', (event) => {
  const target = event.target;
  if (!target) return;

  // Toggle button clicked
  const menuBtn = target.closest(MENU_BTN_SELECTOR);
  if (menuBtn) {
   toggleMobileDrawer(event);
   return;
  }

  // Overlay clicked
  const overlayClicked = target.closest(OVERLAY_SELECTOR);
  if (overlayClicked) {
   event.preventDefault();
   event.stopPropagation();
   closeMobileDrawer();
   return;
  }

  // Outside click / tap: drawer is open and click was outside sidebar
  if (isMobileDrawerOpen()) {
   const currentSidebar = document.getElementById('crmSidebar') || sidebar;
   if (currentSidebar && !currentSidebar.contains(target)) {
    closeMobileDrawer();
   }
  }
 });

 // 2. Global Delegated Touchstart / Pointerdown for instant responsive outside-tap
 document.addEventListener('pointerdown', (event) => {
  if (!isMobileDrawerOpen()) return;
  const target = event.target;
  if (!target) return;

  const overlayClicked = target.closest(OVERLAY_SELECTOR);
  if (overlayClicked) {
   event.preventDefault();
   closeMobileDrawer();
   return;
  }

  const menuBtn = target.closest(MENU_BTN_SELECTOR);
  if (menuBtn) return;

  const currentSidebar = document.getElementById('crmSidebar') || sidebar;
  if (currentSidebar && !currentSidebar.contains(target)) {
   closeMobileDrawer();
  }
 }, { passive: false });

 // 3. Escape Key Closes Mobile Drawer
 document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && isMobileDrawerOpen()) {
   closeMobileDrawer();
  }
 });

 // 4. Resize resets mobile drawer
 window.addEventListener('resize', () => {
  if (window.innerWidth > 900 && isMobileDrawerOpen()) {
   closeMobileDrawer();
  }
 });

 // Ensure overlay exists on load
 if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', ensureOverlay);
 } else {
  ensureOverlay();
 }
})();
