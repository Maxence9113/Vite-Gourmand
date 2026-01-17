/**
 * Table Sort - Tri des colonnes de tableaux
 */

function initTableSort(tableId, options = {}) {
    const table = document.getElementById(tableId);

    if (!table) {
        console.warn(`Table with id "${tableId}" not found`);
        return;
    }

    const excludeColumns = options.excludeColumns || [];
    const headers = table.querySelectorAll('thead th');

    headers.forEach((header, index) => {
        // Skip excluded columns
        if (excludeColumns.includes(index)) {
            return;
        }

        header.classList.add('sortable');
        header.style.cursor = 'pointer';

        header.addEventListener('click', () => {
            sortTable(table, index, header);
        });
    });
}

function sortTable(table, columnIndex, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    // Determine sort direction
    const isAsc = header.classList.contains('asc');
    const newDirection = isAsc ? 'desc' : 'asc';

    // Remove all sort classes from headers
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('asc', 'desc');
    });

    // Add new sort class
    header.classList.add(newDirection);

    // Sort rows
    rows.sort((a, b) => {
        const aCell = a.querySelectorAll('td')[columnIndex];
        const bCell = b.querySelectorAll('td')[columnIndex];

        // Get sort value (use data-sort attribute if available, otherwise text content)
        let aValue = aCell.dataset.sort || aCell.textContent.trim();
        let bValue = bCell.dataset.sort || bCell.textContent.trim();

        // Try to convert to numbers if possible
        const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
        }

        // String comparison
        return newDirection === 'asc'
            ? aValue.localeCompare(bValue, 'fr')
            : bValue.localeCompare(aValue, 'fr');
    });

    // Reorder rows in DOM
    rows.forEach(row => tbody.appendChild(row));
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initTableSort };
}
