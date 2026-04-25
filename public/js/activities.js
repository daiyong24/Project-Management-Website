document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('activity-search');
    const typeFilter = document.getElementById('activity-type-filter');
    const projectFilter = document.getElementById('activity-project-filter');
    const sortSelect = document.getElementById('activity-sort');
    const pageSizeSelect = document.getElementById('activity-page-size');
    const gridCount = document.querySelector('[data-grid-count]');
    const cards = Array.from(document.querySelectorAll('[data-activity-card]'));
    const table = document.querySelector('.activities-grid-table');
    const emptyState = document.getElementById('activity-filter-empty');
    const pagination = document.getElementById('activity-pagination');
    const paginationSummary = document.querySelector('[data-pagination-summary]');
    const prevPageButton = document.getElementById('activity-prev-page');
    const nextPageButton = document.getElementById('activity-next-page');

    const actionModalBackdrop = document.getElementById('activity-action-modal-backdrop');
    const closeActionModalButtons = Array.from(document.querySelectorAll('[data-close-action-modal]'));
    const actionModalTitle = document.getElementById('activity-action-modal-title');
    const actionModalSubtitle = document.getElementById('activity-action-modal-subtitle');
    const actionFeedback = document.getElementById('activity-action-feedback');
    const actionSaveButton = document.getElementById('activity-action-save');
    const actionAssignedTo = document.getElementById('action-assigned-to');
    const actionStatus = document.getElementById('action-status');
    const actionDueDate = document.getElementById('action-due-date');
    const actionDescription = document.getElementById('action-description');
    const actionComment = document.getElementById('action-comment');
    const actionFields = {
        assigned_to: document.querySelector('[data-action-field="assigned_to"]'),
        status: document.querySelector('[data-action-field="status"]'),
        due_date: document.querySelector('[data-action-field="due_date"]'),
        description: document.querySelector('[data-action-field="description"]'),
        comment: document.querySelector('[data-action-field="comment"]'),
    };
    const actionSummary = {
        title: document.getElementById('action-summary-title'),
        project: document.getElementById('action-summary-project'),
        task: document.getElementById('action-summary-task'),
        createdBy: document.getElementById('action-summary-created-by'),
    };

    let currentPage = 1;
    let currentAction = null;
    let currentActionRow = null;

    cards.forEach(function (card) {
        card.dataset.deleted = 'false';
    });

    function formatCount(count) {
        return count + (count === 1 ? ' record' : ' records');
    }

    function toggleActionModal(shouldOpen) {
        if (!actionModalBackdrop) {
            return;
        }

        actionModalBackdrop.hidden = !shouldOpen;
        document.body.style.overflow = shouldOpen ? 'hidden' : '';
    }

    function getStatusWeight(status) {
        const weights = {
            Pending: 1,
            'In Progress': 2,
            Completed: 3,
        };

        return weights[status] || 9;
    }

    function getFilteredRows() {
        const searchValue = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const typeValue = typeFilter ? typeFilter.value : '';
        const projectValue = projectFilter ? projectFilter.value : '';

        return cards.filter(function (card) {
            if (card.dataset.deleted === 'true') {
                return false;
            }

            const matchesSearch = !searchValue || card.dataset.search.includes(searchValue);
            const matchesType = !typeValue || card.dataset.type === typeValue;
            const matchesProject = !projectValue || card.dataset.project === projectValue;

            return matchesSearch && matchesType && matchesProject;
        });
    }

    function sortRows(rows) {
        const mode = sortSelect ? sortSelect.value : 'date_desc';

        rows.sort(function (a, b) {
            if (mode === 'date_asc') {
                return Number(a.dataset.sortDate) - Number(b.dataset.sortDate);
            }

            if (mode === 'project_asc') {
                return a.dataset.project.localeCompare(b.dataset.project);
            }

            if (mode === 'status_asc') {
                return getStatusWeight(a.dataset.status) - getStatusWeight(b.dataset.status);
            }

            return Number(b.dataset.sortDate) - Number(a.dataset.sortDate);
        });
    }

    function renderRows() {
        if (!cards.length || !table) {
            return;
        }

        const filteredRows = getFilteredRows();
        sortRows(filteredRows);

        const pageSize = pageSizeSelect ? Number(pageSizeSelect.value) : 10;
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);

        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = startIndex + pageSize;
        const pagedRows = filteredRows.slice(startIndex, endIndex);

        cards.forEach(function (card) {
            card.hidden = true;
        });

        filteredRows.forEach(function (row) {
            table.appendChild(row);
        });

        pagedRows.forEach(function (row, index) {
            row.hidden = false;
            const numberCell = row.querySelector('[data-row-number]');
            if (numberCell) {
                numberCell.textContent = startIndex + index + 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = filteredRows.length > 0;
        }

        if (gridCount) {
            gridCount.textContent = formatCount(filteredRows.length);
        }

        if (pagination) {
            pagination.hidden = filteredRows.length <= pageSize;
        }

        if (paginationSummary) {
            if (filteredRows.length) {
                paginationSummary.textContent = 'Showing ' + (startIndex + 1) + '-' + Math.min(endIndex, filteredRows.length) + ' of ' + filteredRows.length;
            } else {
                paginationSummary.textContent = 'Showing 0-0 of 0';
            }
        }

        if (prevPageButton) {
            prevPageButton.disabled = currentPage <= 1;
        }

        if (nextPageButton) {
            nextPageButton.disabled = currentPage >= totalPages;
        }
    }

    function setActionFieldVisibility(visibleMap) {
        Object.keys(actionFields).forEach(function (key) {
            const field = actionFields[key];
            if (!field) {
                return;
            }

            field.hidden = !visibleMap[key];
        });
    }

    function showActionFeedback(message, type) {
        if (!actionFeedback) {
            return;
        }

        actionFeedback.textContent = message;
        actionFeedback.hidden = false;
        actionFeedback.classList.remove('success-alert', 'error-alert', 'info-alert');
        actionFeedback.classList.add(type === 'success' ? 'success-alert' : 'info-alert');
    }

    function updateSearchIndex(row) {
        const values = [
            row.dataset.title,
            row.dataset.description,
            row.dataset.type,
            row.dataset.project,
            row.dataset.task,
            row.dataset.createdBy,
            row.dataset.assignedTo,
            row.dataset.status,
            row.dataset.comment,
        ];

        row.dataset.search = values.join(' ').toLowerCase();
    }

    function updateCommentBlock(row, comment) {
        let commentNode = row.querySelector('[data-row-comment]');

        if (!commentNode) {
            const commentCell = row.querySelector('.col-comment');
            commentNode = document.createElement('div');
            commentNode.className = 'comment-note';
            commentNode.setAttribute('data-row-comment', '');
            commentCell.appendChild(commentNode);
        }

        if (comment) {
            commentNode.hidden = false;
            commentNode.classList.remove('is-empty');
            commentNode.textContent = comment;
        } else {
            commentNode.hidden = true;
            commentNode.textContent = '';
            commentNode.classList.add('is-empty');
        }
    }

    function openActionModal(action, row) {
        if (!actionModalBackdrop || !row) {
            return;
        }

        currentAction = action;
        currentActionRow = row;

        const data = row.dataset;

        actionSummary.title.textContent = data.title || '-';
        actionSummary.project.textContent = data.project || '-';
        actionSummary.task.textContent = data.task || '-';
        actionSummary.createdBy.textContent = data.createdBy || '-';

        actionAssignedTo.value = data.assignedTo || '';
        actionStatus.value = data.status || 'Pending';
        actionDueDate.value = data.dueDate || '';
        actionDescription.value = data.description || '';
        actionComment.value = data.comment || '';
        actionFeedback.hidden = true;
        actionSaveButton.hidden = false;

        if (action === 'view') {
            actionModalTitle.textContent = 'Activity Details';
            actionModalSubtitle.textContent = 'Review the current activity log details in read-only mode.';
            setActionFieldVisibility({
                assigned_to: true,
                status: true,
                due_date: true,
                description: true,
                comment: true,
            });
            actionSaveButton.hidden = true;
        } else if (action === 'comment') {
            actionModalTitle.textContent = 'Add Comment';
            actionModalSubtitle.textContent = 'Add a frontend-only comment note without editing the activity record.';
            setActionFieldVisibility({
                assigned_to: false,
                status: false,
                due_date: false,
                description: false,
                comment: true,
            });
            actionSaveButton.textContent = 'Add Comment';
        } else if (action === 'remove') {
            actionModalTitle.textContent = 'Remove Comment';
            actionModalSubtitle.textContent = 'Hide this comment row from the frontend view only.';
            setActionFieldVisibility({
                assigned_to: false,
                status: false,
                due_date: false,
                description: false,
                comment: true,
            });
            actionSaveButton.textContent = 'Remove Comment';
        }

        toggleActionModal(true);
    }

    function applyAction() {
        if (!currentActionRow || !currentAction) {
            return;
        }

        if (currentAction === 'view') {
            toggleActionModal(false);
            return;
        }

        if (currentAction === 'remove') {
            currentActionRow.dataset.deleted = 'true';
            toggleActionModal(false);
            renderRows();
            return;
        }

        if (currentAction === 'comment') {
            currentActionRow.dataset.comment = actionComment.value.trim();
            updateCommentBlock(currentActionRow, currentActionRow.dataset.comment);
            updateSearchIndex(currentActionRow);
            showActionFeedback('Comment note updated in the frontend view.', 'success');
            renderRows();
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentPage = 1;
            renderRows();
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', function () {
            currentPage = 1;
            renderRows();
        });
    }

    if (projectFilter) {
        projectFilter.addEventListener('change', function () {
            currentPage = 1;
            renderRows();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            currentPage = 1;
            renderRows();
        });
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            currentPage = 1;
            renderRows();
        });
    }

    if (prevPageButton) {
        prevPageButton.addEventListener('click', function () {
            currentPage = Math.max(1, currentPage - 1);
            renderRows();
        });
    }

    if (nextPageButton) {
        nextPageButton.addEventListener('click', function () {
            currentPage += 1;
            renderRows();
        });
    }

    closeActionModalButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            toggleActionModal(false);
        });
    });

    if (actionModalBackdrop) {
        actionModalBackdrop.addEventListener('click', function (event) {
            if (event.target === actionModalBackdrop) {
                toggleActionModal(false);
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            toggleActionModal(false);
        }
    });

    cards.forEach(function (row) {
        row.addEventListener('click', function (event) {
            const actionButton = event.target.closest('[data-row-action]');
            if (!actionButton) {
                return;
            }

            openActionModal(actionButton.dataset.rowAction, row);
        });
    });

    if (actionSaveButton) {
        actionSaveButton.addEventListener('click', applyAction);
    }

    renderRows();
});
