(() => {
 document
  .querySelectorAll(
   '[data-crm-menu]'
  )
  .forEach(
   (button) => {
    button.addEventListener(
     'click',
     () => {
      const target =
       document.getElementById(
        button.dataset.crmMenu
       );

      if (!target) {
       return;
      }

      const open =
       target.classList.toggle(
        'open'
       );

      button.setAttribute(
       'aria-expanded',
       open
        ? 'true'
        : 'false'
      );
     }
    );
   }
  );
})();
