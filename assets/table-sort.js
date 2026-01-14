/**
 * Système de tri pour les tableaux d'administration
 * Permet de trier les colonnes par ordre alphabétique, numérique ou par date
 */

class TableSort {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        if (!this.table) {
            console.error(`Table with id "${tableId}" not found`);
            return;
        }

        this.tbody = this.table.querySelector('tbody');
        this.options = {
            excludeColumns: options.excludeColumns || [], // Indices des colonnes à exclure (ex: Actions)
            defaultSort: options.defaultSort || null, // {column: 0, direction: 'asc'}
        };

        this.currentSort = {
            column: null,
            direction: 'asc'
        };

        this.init();
    }

    init() {
        this.addSortableHeaders();
        if (this.options.defaultSort) {
            this.sortTable(this.options.defaultSort.column, this.options.defaultSort.direction);
            this.updateSortIndicators(this.options.defaultSort.column, this.options.defaultSort.direction);
        }
    }

    addSortableHeaders() {
        const headers = this.table.querySelectorAll('thead th');

        headers.forEach((header, index) => {
            // Ne pas rendre triable les colonnes exclues
            if (this.options.excludeColumns.includes(index)) {
                return;
            }

            // Ajouter la classe sortable et le curseur pointer
            header.classList.add('sortable');
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            header.style.position = 'relative';
            header.style.paddingRight = '25px';

            // Ajouter l'indicateur de tri
            const sortIndicator = document.createElement('span');
            sortIndicator.className = 'sort-indicator';
            sortIndicator.innerHTML = '⇅';
            sortIndicator.style.position = 'absolute';
            sortIndicator.style.right = '8px';
            sortIndicator.style.opacity = '0.3';
            sortIndicator.style.fontSize = '0.9em';
            header.appendChild(sortIndicator);

            // Ajouter l'événement de clic
            header.addEventListener('click', () => this.handleHeaderClick(index));
        });
    }

    handleHeaderClick(columnIndex) {
        let direction = 'asc';

        // Si on clique sur la même colonne, inverser la direction
        if (this.currentSort.column === columnIndex) {
            direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        }

        this.sortTable(columnIndex, direction);
        this.updateSortIndicators(columnIndex, direction);
    }

    sortTable(columnIndex, direction) {
        const rows = Array.from(this.tbody.querySelectorAll('tr'));

        // Déterminer le type de données de la colonne
        const firstCell = rows[0]?.cells[columnIndex];
        if (!firstCell) return;

        const dataType = this.detectDataType(firstCell);

        // Trier les lignes
        rows.sort((a, b) => {
            const aCell = a.cells[columnIndex];
            const bCell = b.cells[columnIndex];

            const aValue = this.getCellValue(aCell, dataType);
            const bValue = this.getCellValue(bCell, dataType);

            let comparison = 0;

            if (dataType === 'number') {
                comparison = aValue - bValue;
            } else if (dataType === 'date') {
                comparison = aValue - bValue;
            } else {
                comparison = aValue.localeCompare(bValue, 'fr', { sensitivity: 'base' });
            }

            return direction === 'asc' ? comparison : -comparison;
        });

        // Réorganiser les lignes dans le DOM
        rows.forEach(row => this.tbody.appendChild(row));

        // Mettre à jour l'état actuel
        this.currentSort = { column: columnIndex, direction };
    }

    detectDataType(cell) {
        const text = this.getCellText(cell).trim();

        // Vérifier si c'est une date (format français dd/mm/yyyy ou yyyy-mm-dd)
        if (/^\d{2}\/\d{2}\/\d{4}/.test(text) || /^\d{4}-\d{2}-\d{2}/.test(text)) {
            return 'date';
        }

        // Vérifier si c'est un nombre (avec ou sans €, avec virgule ou point)
        if (/^[\d\s,\.€]+$/.test(text.replace(/\s/g, ''))) {
            return 'number';
        }

        return 'string';
    }

    getCellText(cell) {
        // Chercher d'abord un attribut data-sort
        if (cell.hasAttribute('data-sort')) {
            return cell.getAttribute('data-sort');
        }

        // Sinon, prendre le texte visible (en ignorant les badges et éléments cachés)
        let text = '';

        // Si la cellule contient un badge, prendre son texte
        const badge = cell.querySelector('.badge');
        if (badge) {
            text = badge.textContent.trim();
        } else {
            text = cell.textContent.trim();
        }

        return text;
    }

    getCellValue(cell, dataType) {
        const text = this.getCellText(cell);

        if (dataType === 'number') {
            // Nettoyer et convertir en nombre
            const cleaned = text.replace(/[^\d,.-]/g, '').replace(',', '.');
            return parseFloat(cleaned) || 0;
        } else if (dataType === 'date') {
            // Convertir en timestamp
            return this.parseDate(text);
        } else {
            return text.toLowerCase();
        }
    }

    parseDate(dateString) {
        // Format français: dd/mm/yyyy hh:mm
        const frenchDateMatch = dateString.match(/(\d{2})\/(\d{2})\/(\d{4})/);
        if (frenchDateMatch) {
            const [, day, month, year] = frenchDateMatch;
            const timeMatch = dateString.match(/(\d{2}):(\d{2})/);
            if (timeMatch) {
                const [, hour, minute] = timeMatch;
                return new Date(year, month - 1, day, hour, minute).getTime();
            }
            return new Date(year, month - 1, day).getTime();
        }

        // Format ISO: yyyy-mm-dd
        const isoDateMatch = dateString.match(/(\d{4})-(\d{2})-(\d{2})/);
        if (isoDateMatch) {
            return new Date(dateString).getTime();
        }

        return 0;
    }

    updateSortIndicators(columnIndex, direction) {
        const headers = this.table.querySelectorAll('thead th');

        headers.forEach((header, index) => {
            const indicator = header.querySelector('.sort-indicator');
            if (!indicator) return;

            if (index === columnIndex) {
                // Colonne active
                indicator.innerHTML = direction === 'asc' ? '↑' : '↓';
                indicator.style.opacity = '1';
                indicator.style.color = '#0d6efd';
                indicator.style.fontWeight = 'bold';
            } else {
                // Colonne inactive
                indicator.innerHTML = '⇅';
                indicator.style.opacity = '0.3';
                indicator.style.color = '';
                indicator.style.fontWeight = 'normal';
            }
        });
    }
}

// Fonction helper pour initialiser facilement le tri
function initTableSort(tableId, options = {}) {
    return new TableSort(tableId, options);
}

// Export pour utilisation dans d'autres fichiers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { TableSort, initTableSort };
}