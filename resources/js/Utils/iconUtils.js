export const iconExceptionRules = [
  {
    type: 'Crime',
    conditions: [{ field: 'offense_code', value: '3810' }], // As per prompt: MV/Bicycle collision - INJURY
    className: 'crime-collision-injury-icon',
    // iconUrlOverride: '/images/icons/crime-collision-injury.svg', // Example if specific image file needed
  },
  {
    type: 'Crime',
    conditions: [{ field: 'offense_code', value: '3811' }], // As per prompt: MV/Bicycle collision - NO INJURY
    className: 'crime-collision-no-injury-icon',
    // iconUrlOverride: '/images/icons/crime-collision-no-injury.svg',
  }, /*
  {
    type: 'Crime',
    conditions: [{ field: 'offense_description', contains: 'ASSAULT' }],
    className: 'crime-assault-icon',
    // iconUrlOverride: '/images/icons/crime-assault.svg',
  },
  {
    type: '311 Case',
    // Assuming 'status' or 'case_status' is the field. Adjust if necessary.
    conditions: [{ field: 'case_status', value: 'Open' }], 
    className: 'case-open-icon',
  },
  {
    type: '311 Case',
    conditions: [{ field: 'case_status', value: 'Closed' }],
    className: 'case-closed-icon',
  },
  {
    type: 'Building Permit',
    // Assuming 'est_project_cost' or similar is the field for valuation. Adjust if necessary.
    conditions: [{ field: 'est_project_cost', greaterThan: 1000000 }],
    className: 'permit-high-value-icon',
  }, */
  // Add more rules as needed
];

export function getIconCustomizations(dataPoint) {
  if (!dataPoint || typeof dataPoint !== 'object') {
    return { className: '', iconUrlOverride: null };
  }

  for (const rule of iconExceptionRules) {
    if (dataPoint.alcivartech_type === rule.type) {
      const conditionsMet = rule.conditions.every(condition => {
        const fieldValue = dataPoint[condition.field];

        // Allow undefined field values if explicitly stated in condition, otherwise field must exist
        if (fieldValue === undefined && !condition.allowUndefined) return false;

        if (condition.value !== undefined) {
          return String(fieldValue).toLowerCase() === String(condition.value).toLowerCase();
        }
        if (condition.contains !== undefined) {
          return typeof fieldValue === 'string' && fieldValue.toUpperCase().includes(condition.contains.toUpperCase());
        }
        if (condition.greaterThan !== undefined) {
          const numFieldValue = parseFloat(fieldValue);
          return !isNaN(numFieldValue) && numFieldValue > condition.greaterThan;
        }
        if (condition.lessThan !== undefined) {
          const numFieldValue = parseFloat(fieldValue);
          return !isNaN(numFieldValue) && numFieldValue < condition.lessThan;
        }
        // Add more condition types like 'exists', 'matchesRegex', etc. if needed
        return false; // Fallback for unhandled condition type or if condition is malformed
      });

      if (conditionsMet) {
        return {
          className: rule.className || '',
          iconUrlOverride: rule.iconUrlOverride || null,
        };
      }
    }
  }
  return { className: '', iconUrlOverride: null }; // Default: no customization
}
