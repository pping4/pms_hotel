</div>
<script src="/pms_hotel/assets/js/main.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    if ($('table').length > 0) {
        var table = $('table').DataTable({
            "pageLength": 25,
            "ordering": true,
            "searching": true,
            "fixedHeader": true,
            "dom": 'frtip', // Simplified DOM, no inputs needed
            "initComplete": function() {
                var api = this.api();

                // Add Filter Icon to each column header
                api.columns().every(function() {
                    var column = this;
                    var header = $(column.header());
                    
                    // Skip Action columns or no-filter
                    if (header.hasClass('no-filter') || header.text().trim() === '') {
                        return;
                    }

                    // Append Filter Icon
                    var filterIcon = $('<span class="filter-icon"><i class="fas fa-filter"></i></span>')
                        .appendTo(header)
                        .on('click', function(e) {
                            e.stopPropagation();
                            
                            // Remove existing open menus
                            $('.dt-filter-menu').remove();
                            
                            // Create Menu
                            var menu = $('<div class="dt-filter-menu"></div>')
                                .appendTo('body');
                                
                            // 1. Sort Options
                            menu.append('<div class="menu-item sort-asc"><i class="fas fa-sort-amount-down"></i> Sort A to Z</div>');
                            menu.append('<div class="menu-item sort-desc"><i class="fas fa-sort-amount-up"></i> Sort Z to A</div>');
                            menu.append('<div class="menu-divider"></div>');
                            
                            // 2. Filter Search Box
                            var searchBox = $('<input type="text" class="menu-search" placeholder="Search...">')
                                .appendTo(menu);
                                
                            // 3. Unique Values List
                            var valuesContainer = $('<div class="menu-values"></div>').appendTo(menu);
                            
                            // Get unique data
                            var data = column.data().unique().sort().toArray();
                            
                            // Controls: Select All - Clear
                            var controls = $('<div class="menu-controls">' +
                                '<a href="#" class="action-select-all">Select All</a> - ' +
                                '<a href="#" class="action-clear">Clear</a> ' +
                                '<span class="visible-count" style="color:#999; font-size:0.9em;">(' + data.length + ')</span>' +
                                '</div>').appendTo(valuesContainer);
                            
                            var listContainer = $('<div class="menu-list"></div>').appendTo(valuesContainer);
                            
                            data.forEach(function(d) {
                                var text = $('<div>').html(d).text().trim();
                                if(!text) return;
                                listContainer.append('<label><input type="checkbox" value="'+text+'" checked> '+text+'</label>');
                            });

                            // Position near icon
                            var offset = $(this).offset();
                            menu.css({
                                top: offset.top + 20,
                                left: offset.left
                            });

                            // Handle Events
                            // Sort
                            menu.find('.sort-asc').click(function() { column.order('asc').draw(); menu.remove(); });
                            menu.find('.sort-desc').click(function() { column.order('desc').draw(); menu.remove(); });
                            
                            // Internal Search
                            searchBox.on('keyup', function() {
                                var val = $(this).val().toLowerCase();
                                var labels = listContainer.find('label');
                                var visible = 0;
                                
                                labels.each(function() {
                                    var match = $(this).text().toLowerCase().indexOf(val) > -1;
                                    $(this).toggle(match);
                                    if (match) visible++;
                                });
                                menu.find('.visible-count').text('(' + visible + ')');
                            });
                            
                            // Select All / Clear Actions
                            menu.find('.action-select-all').click(function(e) {
                                e.preventDefault();
                                listContainer.find('label:visible input[type="checkbox"]').prop('checked', true);
                            });
                            
                            menu.find('.action-clear').click(function(e) {
                                e.preventDefault();
                                listContainer.find('label:visible input[type="checkbox"]').prop('checked', false);
                            });
                            
                            // Filter Button
                            menu.append('<div class="menu-footer"><button class="btn-cancel">Cancel</button><button class="btn-apply">Apply</button></div>');
                            
                            menu.find('.btn-cancel').click(function() { menu.remove(); });
                            menu.find('.btn-apply').click(function() {
                                var selected = [];
                                listContainer.find('input[type="checkbox"]:checked').each(function() {
                                    selected.push($.fn.dataTable.util.escapeRegex($(this).attr('value'))); // Use attr to get original text
                                });
                                
                                // Build Regex
                                var val = selected.length > 0 ? selected.join('|') : '';
                                column.search(val ? '^('+val+')$' : '', true, false).draw();
                                menu.remove();
                            });
                            
                            // Close when clicking outside
                            $(document).one('click', function() { menu.remove(); });
                            menu.on('click', function(e) { e.stopPropagation(); });
                        });
                });
            }
        });
    }
});
</script>

<style>
/* Header Filter Icon */
.filter-icon {
    float: right;
    cursor: pointer;
    opacity: 0.3;
    padding-left: 5px;
}
.filter-icon:hover { opacity: 1; }

/* Filter Menu Dropdown */
.dt-filter-menu {
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    padding: 10px;
    z-index: 99999;
    min-width: 200px;
    border-radius: 4px;
    font-size: 13px;
    font-family: inherit;
}
.menu-item {
    padding: 5px 10px;
    cursor: pointer;
}
.menu-item:hover { background: #f0f0f0; }
.menu-divider { border-bottom: 1px solid #eee; margin: 5px 0; }
.menu-search {
    width: 100%;
    margin-bottom: 5px;
    padding: 5px;
    border: 1px solid #ddd;
}
.menu-values {
    border: 1px solid #eee;
    padding: 5px;
    margin-bottom: 10px;
}
.menu-controls {
    padding-bottom: 5px;
    border-bottom: 1px solid #eee;
    margin-bottom: 5px;
    font-size: 0.9em;
}
.menu-controls a {
    text-decoration: none;
    color: #2563eb;
}
.menu-controls a:hover { text-decoration: underline; }
.menu-list {
    max-height: 150px;
    overflow-y: auto;
}
.menu-list label {
    display: block;
    cursor: pointer;
    padding: 2px 0;
}
.menu-footer {
    display: flex;
    justify-content: space-between;
}
.menu-footer button {
    font-size: 12px;
    padding: 4px 10px;
    cursor: pointer;
}
.btn-apply {
    background: #22c55e;
    color: white;
    border: none;
    border-radius: 3px;
}
.btn-cancel {
    background: transparent;
    border: 1px solid #ddd;
    border-radius: 3px;
}
</style>
</body>
</html>
