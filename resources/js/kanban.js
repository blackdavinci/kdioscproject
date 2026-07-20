import Sortable from 'sortablejs';

/**
 * Rend les colonnes d'un kanban triables par glisser-déposer (RGT-06). À chaque
 * dépôt, appelle la méthode Livewire `moveTask(id, statut, position)`.
 *
 * @param {HTMLElement} root  conteneur du board
 * @param {{ call: Function }} wire  composant Livewire ($wire)
 */
window.initKdiKanban = function (root, wire) {
    if (!root || root.dataset.kdiKanbanReady === '1') {
        return;
    }
    root.dataset.kdiKanbanReady = '1';

    root.querySelectorAll('[data-kanban-column]').forEach((column) => {
        new Sortable(column, {
            group: 'kdi-kanban',
            animation: 150,
            ghostClass: 'opacity-50',
            draggable: '[data-task-id]',
            onEnd: (evt) => {
                const id = evt.item.dataset.taskId;
                const status = evt.to.dataset.kanbanColumn;
                wire.call('moveTask', id, status, evt.newIndex);
            },
        });
    });
};
