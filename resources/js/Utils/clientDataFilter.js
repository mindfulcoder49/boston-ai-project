/**
 * Applies a set of filters to an array of data items, mimicking server-side logic.
 * @param {Array<Object>} data The array of data items to filter.
 * @param {Object} filters The filters object.
 * @param {Object} dataTypeDetails Details about the data type.
 * @param {string} dataTypeDetails.dateField The primary date field for date_range filters.
 * @param {Array<string>} [dataTypeDetails.searchableColumns=[]] Columns to search for 'search_term'.
 * @param {Array<Object>} [dataTypeDetails.filterFieldsDescription=[]] Description of filterable fields (name, type, etc.).
 * @returns {Array<Object>} The filtered array of data items.
 */
export function applyClientFilters(data, filters, dataTypeDetails) {
  if (!data || !Array.isArray(data)) return [];
  if (!filters || typeof filters !== 'object') return data;

  let filteredData = [...data];
  const { 
    dateField, 
    searchableColumns = [], 
    filterFieldsDescription = [] 
  } = dataTypeDetails || {};

  const activeFilters = Object.entries(filters).filter(([key, value]) => {
    return value !== null && value !== '' && !(Array.isArray(value) && value.length === 0);
  });

  for (const [key, filterValue] of activeFilters) {
    if (key === 'limit') continue; // 'limit' is not a data filter

    if (key === 'search_term' && typeof filterValue === 'string' && filterValue.trim() !== '' && searchableColumns.length > 0) {
      const searchTerm = filterValue.toLowerCase().trim();
      filteredData = filteredData.filter(item => 
        searchableColumns.some(col => {
          const itemValue = item[col];
          return itemValue && String(itemValue).toLowerCase().includes(searchTerm);
        })
      );
    } else if (key === 'start_date' && dateField) {
      const startDate = new Date(filterValue);
      if (!isNaN(startDate.getTime())) {
        startDate.setHours(0, 0, 0, 0);
        const endDateFilter = filters.end_date ? new Date(filters.end_date) : null;
        if (endDateFilter && !isNaN(endDateFilter.getTime())) {
            endDateFilter.setHours(23, 59, 59, 999);
            filteredData = filteredData.filter(item => {
                const itemDateValue = item[dateField];
                if (!itemDateValue) return false;
                const itemDate = new Date(itemDateValue);
                return !isNaN(itemDate.getTime()) && itemDate >= startDate && itemDate <= endDateFilter;
            });
        } else { // Only start_date provided
            filteredData = filteredData.filter(item => {
                const itemDateValue = item[dateField];
                if (!itemDateValue) return false;
                const itemDate = new Date(itemDateValue);
                return !isNaN(itemDate.getTime()) && itemDate >= startDate;
            });
        }
      }
    } else if (key === 'end_date' && dateField && !filters.start_date) { // Only end_date provided
      const endDate = new Date(filterValue);
      if (!isNaN(endDate.getTime())) {
        endDate.setHours(23, 59, 59, 999);
        filteredData = filteredData.filter(item => {
          const itemDateValue = item[dateField];
          if (!itemDateValue) return false;
          const itemDate = new Date(itemDateValue);
          return !isNaN(itemDate.getTime()) && itemDate <= endDate;
        });
      }
    } else if (key.endsWith('_start') || key.endsWith('_min') || key.endsWith('_end') || key.endsWith('_max')) {
      const isDateRange = key.endsWith('_start') || key.endsWith('_end');
      const isMin = key.endsWith('_start') || key.endsWith('_min');
      const baseColumn = key.substring(0, key.lastIndexOf('_'));

      filteredData = filteredData.filter(item => {
        const itemValue = item[baseColumn];
        if (itemValue === null || typeof itemValue === 'undefined') return false;

        if (isDateRange) {
          const dItemValue = new Date(itemValue);
          const dFilterValue = new Date(filterValue);
          if (isNaN(dItemValue.getTime()) || isNaN(dFilterValue.getTime())) return false; // Invalid date
          return isMin ? dItemValue >= dFilterValue : dItemValue <= dFilterValue;
        } else { // Numeric range
          const numItemValue = parseFloat(itemValue);
          const numFilterValue = parseFloat(filterValue);

          // If either value is not a valid number, the filter doesn't apply to this item.
          if (isNaN(numItemValue) || isNaN(numFilterValue)) {
            return false;
          }

          return isMin ? numItemValue >= numFilterValue : numItemValue <= numFilterValue;
        }
      });
    } else if (Array.isArray(filterValue)) {
      const stringFilterValues = filterValue.map(String);
      filteredData = filteredData.filter(item => {
        const itemValue = item[key];
        return itemValue !== null && typeof itemValue !== 'undefined' && stringFilterValues.includes(String(itemValue));
      });
    } else if (typeof filterValue === 'boolean') {
      filteredData = filteredData.filter(item => {
        let itemBool = item[key];
        if (typeof itemBool === 'string') { // Handle 'true'/'false' strings
          if (itemBool.toLowerCase() === 'true') itemBool = true;
          else if (itemBool.toLowerCase() === 'false') itemBool = false;
        }
        return itemBool === filterValue;
      });
    } else if (key !== 'start_date' && key !== 'end_date') { // General case for other fields (non-range, non-date-range handled above)
      const fieldDef = Array.isArray(filterFieldsDescription) ? filterFieldsDescription.find(f => f.name === key) : null;
      const fieldType = fieldDef?.type;

      if (fieldType === 'number' || (typeof filterValue === 'number' && !isNaN(filterValue))) {
        filteredData = filteredData.filter(item => {
          const nItemValue = parseFloat(item[key]);
          const nFilterValue = parseFloat(filterValue);
          return !isNaN(nItemValue) && nItemValue === nFilterValue;
        });
      } else { // Default to string "contains"
        const sFilterValue = String(filterValue).toLowerCase();
        filteredData = filteredData.filter(item => {
          const itemValue = item[key];
          return itemValue && String(itemValue).toLowerCase().includes(sFilterValue);
        });
      }
    }
  }
  return filteredData;
}
