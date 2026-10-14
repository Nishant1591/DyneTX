/** One persistent biography dialog, matching the original #modalTeam behavior. */
(function (Drupal, once) {
  'use strict';

  function teamDialog() {
    let modal = document.getElementById('modalTeam');
    if (modal) return modal;
    const shell = document.getElementById('dyne-team-dialog-template');
    if (!shell) return null;
    document.body.appendChild(shell.content.cloneNode(true));
    modal = document.getElementById('modalTeam');
    let currentBio;
    let origin;

    function selectBiography(id, announce = false) {
      const bio = document.getElementById(id);
      if (!(bio instanceof HTMLTemplateElement)) return false;
      const row = modal.querySelector('[data-dyne-team-content]');
      Drupal.detachBehaviors(row);
      row.replaceChildren(bio.content.cloneNode(true));
      const title = row.querySelector('[data-dyne-team-title]');
      title.id = 'modalTeamTitle';
      modal.classList.toggle('noimage', bio.dataset.dyneTeamNoimage === 'true');
      modal.querySelector('.modal-footer').hidden = Number(bio.dataset.dyneTeamCount) < 2;
      modal.querySelector('[data-dyne-team-announcement]').textContent = announce ? title.textContent : '';
      currentBio = bio;
      modal.dataset.dyneTeamCurrent = id;
      Drupal.attachBehaviors(row);
      // Recalculate scrollbar compensation without hiding, refocusing or
      // scrolling the dialog. Native scrolling only clamps shorter biographies.
      window.bootstrap?.Modal.getInstance(modal)?.handleUpdate();
      return true;
    }

    modal.addEventListener('show.bs.modal', (event) => {
      const trigger = event.relatedTarget;
      if (!trigger || !selectBiography(trigger.dataset.dyneTeamBio)) {
        event.preventDefault();
        return;
      }
      origin = trigger;
    });
    modal.addEventListener('hidden.bs.modal', () => {
      if (origin?.isConnected) origin.focus();
    });
    modal.querySelectorAll('[data-dyne-team-direction]').forEach((button) => {
      button.addEventListener('click', () => {
        if (!currentBio) return;
        const next = button.dataset.dyneTeamDirection === 'next';
        selectBiography(next ? currentBio.dataset.dyneTeamNext : currentBio.dataset.dyneTeamPrev, true);
      });
    });
    return modal;
  }

  Drupal.behaviors.dyneTeam = {
    attach(context) {
      // Keeping the shell outside the Paragraph avoids transformed/clipped
      // ancestors. Drupal's once API also supports cards inserted through AJAX.
      if (once('dyne-team-card', '[data-dyne-team-bio]', context).length) teamDialog();
    },
  };
})(Drupal, once);
