const SHEET_NAME = '201 file';
const DASHBOARD_SHEET_NAME = 'Employee Detail';
const SUMMARY_SHEET_NAME = 'Dashboard';
const DATA_ENTRY_SHEET_NAME = 'Data Entry';
const PROFILE_SHEET_NAME = 'Profile';
const HEADER_ROW = 3;
const DATA_START_ROW = HEADER_ROW + 1;
const SEARCH_INPUT_CELL = 'B2';
const SEARCH_STATUS_CELL = 'C2';
const DASHBOARD_EMPLOYEE_ID_CELL = 'J8';
const DATA_ENTRY_SUBMIT_CELL = 'M5';
const DASHBOARD_TOTAL_FILTER_CELL = 'B11';
const DASHBOARD_ACTIVE_FILTER_CELL = 'D11';
const DASHBOARD_INACTIVE_FILTER_CELL = 'F11';
const DASHBOARD_LICENSED_FILTER_CELL = 'H11';
const DASHBOARD_FULL_TIME_FILTER_CELL = 'J11';
const DASHBOARD_PART_TIME_FILTER_CELL = 'L11';
const DASHBOARD_DEPARTMENT_FILTER_CELL = 'N11';
const DASHBOARD_LATEST_YEAR_FILTER_CELL = 'P11';
const DASHBOARD_YEAR_FILTER_CELL = 'N49';

const HEADERS = [
  'Timestamp',
  'Name',
  'ID number',
  'Account No.',
  'sex',
  'civil status',
  'address',
  'contact no.',
  'date of birth',
  'age to date',
  'employment date',
  'length of service',
  'position',
  'department',
  'class',
  'rank',
  'grade',
  'sss',
  'tin',
  'philhealth',
  'pag-ibig mid',
  'pag-ibig rtn',
  'bachelor',
  "master's degree",
  'doctorate degree',
  'with/without license',
  'eligibility',
  'registration no',
  'registration date',
  'valid until',
  'rate per hour',
  'basic salary',
  'allowance',
  'date resigned',
  'employement history',
  'profile picture'
];

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('HRIS')
    .addItem('Setup HRIS Workbook', 'setupHrisWorkbook')
    .addSeparator()
    .addItem('Open Data Entry', 'openDataEntry')
    .addItem('Submit', 'submitDataEntry')
    .addSeparator()
    .addItem('Open Profile', 'openProfile')
    .addItem('Open Employee Detail', 'openEmployeeDetail')
    .addItem('Refresh Employee Detail', 'refreshEmployeeDetail')
    .addItem('Open Dashboard', 'openDashboard')
    .addItem('Refresh Dashboard', 'refreshDashboard')
    .addItem('Install Shared Submit Trigger', 'installSharedSubmitTrigger')
    .addItem('Clear Sheet Search', 'clearEmployeeRecordSearch')
    .addToUi();

  ensureEmployeeSheet_();
}

function setupHrisWorkbook() {
  const employeeSheet = ensureEmployeeSheet_();
  buildDataEntrySheet_();
  buildProfileSheet_();
  buildEmployeeDetailSheet_(employeeSheet);
  buildDashboardSheet_(employeeSheet);
  SpreadsheetApp.getActiveSpreadsheet().toast('HRIS workbook is ready.', 'HRIS', 4);
}

function installSharedSubmitTrigger() {
  const spreadsheet = SpreadsheetApp.getActive();
  ScriptApp.getProjectTriggers().forEach(function (trigger) {
    if (trigger.getHandlerFunction() === 'handleSharedEdit_') {
      ScriptApp.deleteTrigger(trigger);
    }
  });

  ScriptApp.newTrigger('handleSharedEdit_')
    .forSpreadsheet(spreadsheet)
    .onEdit()
    .create();

  PropertiesService.getDocumentProperties().setProperty('HRIS_SHARED_EDIT_TRIGGER_INSTALLED', 'TRUE');
  SpreadsheetApp.getActiveSpreadsheet().toast('Shared submit trigger installed. Coworkers can submit by checkbox without running menu actions.', 'HRIS', 6);
}

function onEdit(event) {
  if (isSharedEditTriggerInstalled_()) return;
  handleSharedEdit_(event);
}

function handleSharedEdit_(event) {
  if (!event || !event.range) return;

  const range = event.range;
  const sheet = range.getSheet();

  if (sheet.getName() === SUMMARY_SHEET_NAME && isDashboardFilterCell_(range.getA1Notation())) {
    if (hasCheckedDashboardFilter_(sheet)) {
      applyDashboardCombinedFilters_(sheet);
      return;
    }

    showDashboardEmployeeTable_(sheet, getEmployeeRecords_(ensureEmployeeSheet_()), 'All Employees');
    return;
  }

  if (sheet.getName() === SUMMARY_SHEET_NAME && range.getA1Notation() === DASHBOARD_YEAR_FILTER_CELL) {
    applyDashboardYearFilter_(sheet, event.value || '');
    return;
  }

  if (sheet.getName() === DATA_ENTRY_SHEET_NAME && doesRangeTouchA1_(range, DATA_ENTRY_SUBMIT_CELL)) {
    if (String(sheet.getRange(DATA_ENTRY_SUBMIT_CELL).getValue()).toUpperCase() === 'TRUE') {
      submitDataEntry();
      sheet.getRange(DATA_ENTRY_SUBMIT_CELL).setValue(false);
    }
    return;
  }

  if (sheet.getName() === DASHBOARD_SHEET_NAME && range.getA1Notation() === DASHBOARD_EMPLOYEE_ID_CELL) {
    buildEmployeeDetailSheet_(ensureEmployeeSheet_());
    return;
  }

  if (sheet.getName() !== SHEET_NAME) return;

  if (range.getA1Notation() === SEARCH_INPUT_CELL) {
    applyEmployeeRecordSearch_(event.value || '');
    return;
  }

  updateComputedDateFields_(sheet, range);
  buildEmployeeDetailSheet_(sheet);
  buildDashboardSheet_(sheet);
}

function isSharedEditTriggerInstalled_() {
  try {
    return PropertiesService.getDocumentProperties().getProperty('HRIS_SHARED_EDIT_TRIGGER_INSTALLED') === 'TRUE';
  } catch (error) {
    return false;
  }
}

function onSelectionChange(event) {
  if (!event || !event.range) return;

  const range = event.range;
  const sheet = range.getSheet();
  if (sheet.getName() !== SUMMARY_SHEET_NAME) return;
  const employees = getEmployeeRecords_(ensureEmployeeSheet_());
  const row = range.getRow();
  const column = range.getColumn();
  const isMetricRow = row >= 7 && row <= 10;
  if (!isMetricRow) return;

  if (column >= 1 && column <= 2) {
    showDashboardEmployeeTable_(sheet, employees, 'All Employees');
    return;
  }

  if (column >= 3 && column <= 4) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return !employee.dateResigned;
    }), 'Active Employees');
    return;
  }

  if (column >= 5 && column <= 6) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return employee.dateResigned;
    }), 'Inactive Employees');
    return;
  }

  if (column >= 7 && column <= 8) {
    showDashboardEmployeeTable_(sheet, employees.filter(isLicensedEmployee_), 'Licensed Employees');
    return;
  }

  if (column >= 9 && column <= 10) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return isFullTimeEmployee_(employee);
    }), 'Full-Time Employees');
    return;
  }

  if (column >= 11 && column <= 12) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return isPartTimeEmployee_(employee);
    }), 'Part-Time Employees');
    return;
  }

  if (column >= 13 && column <= 14) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return String(employee.department || '').trim();
    }), 'Employees With Department');
    return;
  }

  if (column >= 15 && column <= 16) {
    const latestYear = getLatestJoinYear_(employees);
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      const employmentDate = parseFormDate_(employee.employmentDate);
      return employmentDate && employmentDate.getFullYear() === latestYear;
    }), 'Employees Joined in ' + (latestYear || 'Latest Year'));
  }
}

function openDataEntry() {
  SpreadsheetApp.getActiveSpreadsheet().setActiveSheet(ensureDataEntrySheet_());
}

function openEmployeeDetail() {
  SpreadsheetApp.getActiveSpreadsheet().setActiveSheet(buildEmployeeDetailSheet_(ensureEmployeeSheet_()));
}

function openProfile() {
  SpreadsheetApp.getActiveSpreadsheet().setActiveSheet(rebuildProfileSheetFromRecords_());
}

function fixProfileHeaderWidth() {
  const sheet = getOrCreateSheet_(PROFILE_SHEET_NAME);
  setupTopNav_(sheet);
  applyProfileHeaderLayout_(sheet);
  sheet.getRange('A1:P60').setFontFamily('Arial').setVerticalAlignment('middle');
  SpreadsheetApp.getActiveSpreadsheet().toast('Profile header adjusted to column P.', 'Profile', 3);
}

function unhidePictureLinkColumn() {
  const sheet = ensureEmployeeSheet_();
  const profilePictureColumn = HEADERS.indexOf('profile picture') + 1;
  if (profilePictureColumn > 0) {
    sheet.showColumns(profilePictureColumn);
    sheet.setColumnWidth(profilePictureColumn, 220);
  }
  SpreadsheetApp.getActiveSpreadsheet().toast('Picture link column is visible.', '201 file', 3);
}

function moveSearchStatusToC2() {
  const sheet = ensureEmployeeSheet_();
  sheet.getRange('M2').clearContent();
  sheet.getRange('C2').setValue('Type in B2 to search')
    .setBackground('#e9f5f3')
    .setFontColor('#0f766e')
    .setFontWeight('bold')
    .setVerticalAlignment('middle')
    .setFontFamily('Arial');
  SpreadsheetApp.getActiveSpreadsheet().toast('Search status moved to C2.', '201 file', 3);
}

function moveSectionTabsToD2() {
  setupEmployeeRecordTabs_(ensureEmployeeSheet_());
  SpreadsheetApp.getActiveSpreadsheet().toast('Section tabs moved to D2.', 'HRIS', 3);
}

function refreshEmployeeDetail() {
  openEmployeeDetail();
}

function openDashboard() {
  SpreadsheetApp.getActiveSpreadsheet().setActiveSheet(buildDashboardSheet_(ensureEmployeeSheet_()));
}

function refreshDashboard() {
  openDashboard();
}

function submitDataEntry() {
  const sheet = ensureDataEntrySheet_();
  const data = readDataEntrySheet_(sheet);

  if (!data.lastName && !data.firstName && !data.idNumber) {
    SpreadsheetApp.getActiveSpreadsheet().toast('Please enter at least a name or ID number.', 'Data Entry', 4);
    return;
  }

  try {
    saveEmployeeRecord(data);
  } catch (error) {
    SpreadsheetApp.getActiveSpreadsheet().toast('Could not save to 201 file: ' + error.message, 'Data Entry', 6);
    throw error;
  }

  try {
    appendEmployeeProfile_(data);
  } catch (error) {
    SpreadsheetApp.getActiveSpreadsheet().toast('Saved to 201 file, but Profile was not updated: ' + error.message, 'Data Entry', 6);
  }

  try {
    updateDashboardSnapshot_();
  } catch (error) {
    SpreadsheetApp.getActiveSpreadsheet().toast('Saved to 201 file, but Dashboard was not updated: ' + error.message, 'Data Entry', 6);
  }

  try {
    updateEmployeeDetailSnapshot_();
  } catch (error) {
    SpreadsheetApp.getActiveSpreadsheet().toast('Saved to 201 file, but Employee Detail was not updated: ' + error.message, 'Data Entry', 6);
  }

  clearDataEntrySheet_(sheet);
  SpreadsheetApp.getActiveSpreadsheet().toast('Employee record saved to 201 file.', 'Data Entry', 4);
}

function saveEmployeeRecord(data) {
  const sheet = getEmployeeSheetForWrite_();
  const fullName = [data.firstName, data.middleName].filter(Boolean).join(' ');
  const name = [data.lastName, fullName].filter(Boolean).join(', ');
  const today = new Date();

  const row = [
    new Date(),
    name,
    data.idNumber || '',
    data.accountNo || '',
    data.sex || '',
    data.civilStatus || '',
    data.address || '',
    data.contactNumber || '',
    data.dateOfBirth || '',
    formatAge_(data.dateOfBirth, today),
    data.employmentDate || '',
    formatDateDifference_(data.employmentDate, today),
    data.position || '',
    data.department || '',
    formatClassificationForRecord_(data.classification),
    '',
    '',
    data.sss || '',
    data.tin || '',
    data.philHealth || '',
    data.pagIbigMid || '',
    data.pagIbigRtn || '',
    data.bachelorsDegree || '',
    data.mastersDegree || '',
    data.doctorateDegree || '',
    normalizeLicenseValue_(data.license),
    '',
    data.registrationNo || '',
    data.registrationDate || '',
    data.validUntil || '',
    data.ratePerHour || '',
    data.basicSalary || '',
    data.allowance || '',
    '',
    '',
    normalizePictureUrl_(data.pictureUrl)
  ];

  const nextRow = Math.max(sheet.getLastRow() + 1, DATA_START_ROW);
  sheet.getRange(nextRow, 1, 1, row.length).setValues([row]);
  formatNewEmployeeRecordRow_(sheet, nextRow);
  SpreadsheetApp.flush();
}

function getEmployeeSheetForWrite_() {
  const sheet = getOrCreateSheet_(SHEET_NAME);
  const headers = sheet.getRange(HEADER_ROW, 1, 1, HEADERS.length).getValues()[0];
  const hasHeaders = headers.some(function (header) {
    return String(header || '').trim();
  });

  if (!hasHeaders) {
    sheet.getRange(HEADER_ROW, 1, 1, HEADERS.length).setValues([HEADERS]);
  }

  return sheet;
}

function getEmployeeSheetForRead_() {
  return getOrCreateSheet_(SHEET_NAME);
}

function updateDashboardSnapshot_() {
  const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = spreadsheet.getSheetByName(SUMMARY_SHEET_NAME);
  if (!sheet || sheet.getRange('A3').getValue() !== 'HRIS Dashboard') {
    buildDashboardSheet_(getEmployeeSheetForRead_());
    return;
  }

  const employees = getEmployeeRecords_(getEmployeeSheetForRead_());
  const activeCount = employees.filter(function (employee) { return !employee.dateResigned; }).length;
  const inactiveCount = employees.filter(function (employee) { return employee.dateResigned; }).length;
  const licensedCount = employees.filter(isLicensedEmployee_).length;
  const fullTimeCount = employees.filter(isFullTimeEmployee_).length;
  const partTimeCount = employees.filter(isPartTimeEmployee_).length;
  const genderRows = summarizeBy_(employees, 'sex');
  const departmentRows = summarizeBy_(employees, 'department');
  const yearRows = summarizeJoinYears_(employees);
  const ageRows = summarizeAgeRanges_(employees);
  const departmentPerformanceRows = summarizeDepartmentPerformance_(employees);

  setMetricCard_(sheet, 'A7:B10', 'Total Employees', employees.length, '#e0f2fe', '#0369a1');
  setMetricCard_(sheet, 'C7:D10', 'Active', activeCount, '#dcfce7', '#15803d');
  setMetricCard_(sheet, 'E7:F10', 'Inactive', inactiveCount, '#fee2e2', '#b91c1c');
  setMetricCard_(sheet, 'G7:H10', 'Licensed', licensedCount, '#f5f3ff', '#7c3aed');
  setMetricCard_(sheet, 'I7:J10', 'Full-Time', fullTimeCount, '#fff7ed', '#c2410c');
  setMetricCard_(sheet, 'K7:L10', 'Part-Time', partTimeCount, '#fef3c7', '#b45309');
  setMetricCard_(sheet, 'M7:N10', 'Departments', departmentRows.length, '#ecfdf5', '#047857');
  setMetricCard_(sheet, 'O7:P10', 'Latest Join Year', getLatestJoinYear_(employees) || '-', '#f1f5f9', '#334155');
  setDashboardGenderPie_(sheet, 'A13:E29', genderRows, '#0f766e');
  setDashboardDepartmentPie_(sheet, 'F13:K29', departmentRows, '#2563eb');
  setDashboardLinePanel_(sheet, 'L13:P29', 'Year Joined Trend', yearRows, '#7c3aed');
  setDashboardVisualTable_(sheet, 'A32:F47', 'Age Band Distribution', ageRows, '#c2410c');
  setDashboardVisualTable_(sheet, 'G32:P47', 'Department Performance', departmentPerformanceRows, '#047857');
  renderDashboardEmployeeTable_(sheet, employees, 'All Employees', 50);
}

function updateEmployeeDetailSnapshot_() {
  buildEmployeeDetailSheet_(getEmployeeSheetForRead_());
}

function formatNewEmployeeRecordRow_(sheet, rowNumber) {
  const lastColumn = HEADERS.length;
  sheet.getRange(rowNumber, 1, 1, lastColumn)
    .setFontFamily('Arial')
    .setFontSize(10)
    .setVerticalAlignment('middle')
    .setFontColor('#1f2937')
    .setBackground(rowNumber % 2 === 0 ? '#f8fafc' : '#ffffff')
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID)
    .setWrap(true);

  sheet.getRange(rowNumber, 1).setNumberFormat('mmm d, yyyy h:mm AM/PM');
  sheet.getRange(rowNumber, 9).setNumberFormat('mmm d, yyyy');
  sheet.getRange(rowNumber, 11).setNumberFormat('mmm d, yyyy');
  sheet.getRange(rowNumber, 29).setNumberFormat('mmm d, yyyy');
  sheet.getRange(rowNumber, 30).setNumberFormat('mmm d, yyyy');
  sheet.getRange(rowNumber, 31, 1, 3).setNumberFormat('#,##0.00');
}

function appendEmployeeProfile_(data) {
  const sheet = buildProfileSheet_();
  const nextBlock = getNextProfileBlockStartRow_(sheet);
  writeEmployeeProfileBlock_(sheet, nextBlock, data);
}

function saveEmployeeProfile(data) {
  appendEmployeeProfile_(data);
}

function writeEmployeeProfileBlock_(sheet, nextBlock, data) {
  const fullName = [data.firstName, data.middleName].filter(Boolean).join(' ');
  const name = [data.lastName, fullName].filter(Boolean).join(', ');
  const pictureUrl = normalizePictureUrl_(data.pictureUrl);

  sheet.getRange(nextBlock, 1, 8, 6)
    .breakApart()
    .clearContent()
    .clearFormat()
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(nextBlock, 1, 8, 2).merge()
    .setBackground('#f8fafc')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#334155', SpreadsheetApp.BorderStyle.SOLID);

  if (pictureUrl) {
    sheet.getRange(nextBlock, 1)
      .setFormula('=IMAGE("' + escapeFormulaText_(pictureUrl) + '",4,160,160)');
  } else {
    sheet.getRange(nextBlock, 1)
      .setValue('No Picture')
      .setFontColor('#64748b')
      .setFontWeight('bold');
  }

  sheet.getRange(nextBlock, 3, 1, 4).merge()
    .setValue(name || 'Unnamed Employee')
    .setBackground('#0f766e')
    .setFontColor('#ffffff')
    .setFontSize(16)
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  const rows = [
    ['ID Number:', data.idNumber || ''],
    ['Position:', data.position || ''],
    ['Department:', data.department || ''],
    ['Contact:', data.contactNumber || ''],
    ['Address:', data.address || ''],
    ['License:', normalizeLicenseValue_(data.license)],
    ['Basic Salary:', data.basicSalary || '']
  ];

  rows.forEach(function (row, index) {
    const targetRow = nextBlock + index + 1;
    sheet.getRange(targetRow, 3).setValue(row[0]).setFontWeight('bold').setBackground('#e9f5f3');
    sheet.getRange(targetRow, 4, 1, 3).merge().setValue(row[1]).setBackground('#ffffff').setWrap(true);
  });

  sheet.getRange(nextBlock, 1, 8, 6)
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setFontFamily('Arial');
  sheet.setRowHeights(nextBlock, 8, 28);
  sheet.setRowHeight(nextBlock, 34);
}

function rebuildProfileSheetFromRecords_() {
  const sheet = buildProfileSheet_();
  clearProfileRecordBlocks_(sheet);
  const employees = getEmployeeRecords_(getEmployeeSheetForRead_());

  employees.forEach(function (employee, index) {
    writeEmployeeProfileBlock_(sheet, 7 + (index * 9), employeeRecordToProfileData_(employee));
  });

  SpreadsheetApp.getActiveSpreadsheet().toast('Profile updated: ' + employees.length + ' employee(s)', 'Profile', 3);
  return sheet;
}

function clearProfileRecordBlocks_(sheet) {
  const lastRow = Math.max(sheet.getLastRow(), 60);
  if (lastRow < 7) return;
  sheet.getRange(7, 1, lastRow - 6, 6)
    .breakApart()
    .clearContent()
    .clearFormat();
}

function employeeRecordToProfileData_(employee) {
  const nameParts = splitEmployeeRecordName_(employee.name);
  return {
    lastName: nameParts.lastName,
    firstName: nameParts.firstName,
    middleName: nameParts.middleName,
    idNumber: employee.idNumber || '',
    position: employee.position || '',
    department: employee.department || '',
    contactNumber: employee.contactNo || '',
    address: employee.address || '',
    license: normalizeLicenseValue_(employee.license),
    basicSalary: employee.basicSalary || '',
    pictureUrl: employee.pictureUrl || ''
  };
}

function splitEmployeeRecordName_(name) {
  const parts = String(name || '').split(',');
  const lastName = String(parts[0] || '').trim();
  const givenNames = String(parts.slice(1).join(',') || '').trim().split(/\s+/).filter(Boolean);
  return {
    lastName: lastName,
    firstName: givenNames[0] || '',
    middleName: givenNames.slice(1).join(' ')
  };
}

function buildProfileSheet_() {
  const sheet = getOrCreateSheet_(PROFILE_SHEET_NAME);
  sheet.setFrozenRows(0);
  sheet.setFrozenColumns(0);
  sheet.setHiddenGridlines(true);

  if (sheet.getRange('A3').getValue() !== 'HRIS Employee Profiles') {
    sheet.clear();
    setupTopNav_(sheet);
    applyProfileHeaderLayout_(sheet);
    [150, 150, 120, 150, 150, 150, 120, 120, 120, 120, 120, 120, 120, 120, 120, 120].forEach(function (width, index) {
      sheet.setColumnWidth(index + 1, width);
    });
    for (let row = 1; row <= 60; row++) sheet.setRowHeight(row, 28);
    sheet.setFrozenRows(5);
    sheet.getRange('A1:P60').setFontFamily('Arial').setVerticalAlignment('middle');
  } else {
    applyProfileHeaderLayout_(sheet);
  }

  return sheet;
}

function applyProfileHeaderLayout_(sheet) {
  sheet.getRange('A3:P4').breakApart().merge()
    .setValue('HRIS Employee Profiles')
    .setBackground('#0f172a')
    .setFontColor('#ffffff')
    .setFontSize(20)
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');
  sheet.getRange('A5:P5').breakApart().merge()
    .setValue('Submitted Data Entry profiles with picture previews')
    .setBackground('#e9f5f3')
    .setFontColor('#0f766e')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');
}

function getNextProfileBlockStartRow_(sheet) {
  const startRow = 7;
  const blockSize = 9;
  let row = startRow;

  while (sheet.getRange(row, 3).getValue()) {
    row += blockSize;
  }

  return row;
}

function ensureEmployeeSheet_() {
  const sheet = getOrCreateSheet_(SHEET_NAME);
  setupEmployeeSearchBar_(sheet);
  sheet.getRange(HEADER_ROW, 1, 1, HEADERS.length).setValues([HEADERS]);
  formatEmployeeSheet_(sheet);
  return sheet;
}

function ensureDataEntrySheet_() {
  const sheet = getOrCreateSheet_(DATA_ENTRY_SHEET_NAME);
  const validation = sheet.getRange(DATA_ENTRY_SUBMIT_CELL).getDataValidation();
  const hasSubmitCheckbox = validation &&
    validation.getCriteriaType() === SpreadsheetApp.DataValidationCriteria.CHECKBOX;

  if (sheet.getRange('C2').getValue() !== 'Employee Information Form' || !hasSubmitCheckbox) {
    return buildDataEntrySheet_();
  }

  applyDataEntryValidations_(sheet);
  return sheet;
}

function buildDataEntrySheet_() {
  const sheet = getOrCreateSheet_(DATA_ENTRY_SHEET_NAME);
  sheet.setFrozenRows(0);
  sheet.setFrozenColumns(0);
  sheet.getRange('A1:P55').breakApart();
  sheet.clear();
  sheet.setHiddenGridlines(true);

  setupTopNav_(sheet);

  sheet.getRange('A2:B5').merge()
    .setValue('HRIS')
    .setBackground('#e8f5e9')
    .setFontColor('#0f766e')
    .setFontSize(20)
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#99d6cd', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange('C2:J2').merge()
    .setValue('Employee Information Form')
    .setFontColor('#0f172a')
    .setFontSize(22)
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  sheet.getRange('C3:J3').merge()
    .setValue('201 File - Data Entry View')
    .setFontColor('#334155')
    .setFontStyle('italic')
    .setHorizontalAlignment('center');

  sheet.getRange('C4:J4').merge()
    .setValue('Fill in the Data column, then check Submit.')
    .setFontColor('#0f766e')
    .setHorizontalAlignment('center');

  sheet.getRange('K2:L2').merge()
    .setValue('Picture Link')
    .setBackground('#f8fafc')
    .setFontColor('#334155')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#334155', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange('K3:L4').merge()
    .setBackground('#ffffff')
    .setWrap(true)
    .setVerticalAlignment('top')
    .setBorder(true, true, true, true, true, true, '#334155', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange('K5:L5').merge()
    .setValue('Submit')
    .setBackground('#dcfce7')
    .setFontColor('#15803d')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#15803d', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(DATA_ENTRY_SUBMIT_CELL)
    .insertCheckboxes()
    .setValue(false)
    .setBackground('#dcfce7')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#15803d', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange('C5:D5').merge()
    .setValue('Employee ID')
    .setBackground('#f8fafc')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setBorder(true, true, true, true, true, true, '#334155', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange('E5:G5').merge()
    .setBackground('#ffffff')
    .setBorder(true, true, true, true, true, true, '#334155', SpreadsheetApp.BorderStyle.SOLID);

  buildDataEntryTable_(sheet);

  sheet.getRangeList(['F16', 'F19', 'F28', 'F29']).setNumberFormat('mmm d, yyyy');
  sheet.getRangeList(['F35', 'F36', 'F37']).setNumberFormat('#,##0.00');
  applyDataEntryValidations_(sheet);

  [45, 110, 110, 110, 130, 260, 140, 110, 110, 110, 110, 110, 90].forEach(function (width, index) {
    sheet.setColumnWidth(index + 1, width);
  });

  for (let row = 1; row <= 55; row++) sheet.setRowHeight(row, 24);
  sheet.setRowHeight(1, 36);
  sheet.setRowHeight(2, 32);
  sheet.setRowHeight(5, 30);
  sheet.setFrozenRows(9);
  sheet.getRange('A1:P55').setFontFamily('Arial').setFontSize(10).setVerticalAlignment('middle');

  return sheet;
}

function applyDataEntryValidations_(sheet) {
  sheet.getRange('F14').setDataValidation(SpreadsheetApp.newDataValidation().requireValueInList(['Female', 'Male'], true).setAllowInvalid(false).build());
  sheet.getRange('F15').setDataValidation(SpreadsheetApp.newDataValidation().requireValueInList(['Single', 'Married', 'Widowed', 'Separated'], true).setAllowInvalid(false).build());
  sheet.getRange('F22').setDataValidation(SpreadsheetApp.newDataValidation().requireValueInList(['Full-Time', 'Part-Time', 'Non-Teaching'], true).setAllowInvalid(false).build());
}

function buildDataEntryTable_(sheet) {
  const fields = getDataEntryTableFields_();
  const tableStartRow = 9;
  const tableRows = fields.length + 1;

  sheet.getRange(tableStartRow, 1, 1, 13)
    .setValues([['SL', 'Group', '', 'Title', '', 'Data', '', '', '', '', '', '', 'Note']])
    .setBackground('#0f4c75')
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setBorder(true, true, true, true, true, true, '#082f49', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(tableStartRow, 2, 1, 2).mergeAcross();
  sheet.getRange(tableStartRow, 4, 1, 2).mergeAcross();
  sheet.getRange(tableStartRow, 6, 1, 7).mergeAcross();

  fields.forEach(function (field, index) {
    const row = tableStartRow + index + 1;
    const groupColor = getDataEntryGroupColor_(field.group);

    sheet.getRange(row, 1).setValue(index + 1).setHorizontalAlignment('center');
    sheet.getRange(row, 2, 1, 2).mergeAcross().setValue(index === field.groupStart ? field.group : '').setBackground(groupColor);
    sheet.getRange(row, 4, 1, 2).mergeAcross().setValue(field.title).setBackground(groupColor).setFontWeight('bold');
    sheet.getRange(row, 6, 1, 7).mergeAcross().setBackground('#ffffff');
    sheet.getRange(row, 13).setBackground(groupColor);
    sheet.getRange(row, 1, 1, 13).setBorder(true, true, true, true, true, true, '#94a3b8', SpreadsheetApp.BorderStyle.SOLID);
  });

  mergeDataEntryGroupLabels_(sheet, fields, tableStartRow);
  sheet.getRange(tableStartRow, 1, tableRows, 13).setWrap(true);
  sheet.getRange(tableStartRow + 1, 6, fields.length, 7).setHorizontalAlignment('left');
}

function getDataEntryTableFields_() {
  const rows = [
    ['Personal Details', 'Last Name:', 'F10', 'lastName'],
    ['Personal Details', 'First Name:', 'F11', 'firstName'],
    ['Personal Details', 'Middle Name:', 'F12', 'middleName'],
    ['Personal Details', 'Account No.:', 'F13', 'accountNo'],
    ['Personal Details', 'Sex:', 'F14', 'sex'],
    ['Personal Details', 'Civil Status:', 'F15', 'civilStatus'],
    ['Personal Details', 'Date of Birth:', 'F16', 'dateOfBirth'],
    ['Personal Details', 'Contact Number:', 'F17', 'contactNumber'],
    ['Personal Details', 'Address:', 'F18', 'address'],
    ['Job Details', 'Employment Date:', 'F19', 'employmentDate'],
    ['Job Details', 'Position:', 'F20', 'position'],
    ['Job Details', 'Department:', 'F21', 'department'],
    ['Job Details', 'Classification:', 'F22', 'classification'],
    ['Educational Background', "Bachelor's Degree:", 'F23', 'bachelorsDegree'],
    ['Educational Background', "Master's Degree:", 'F24', 'mastersDegree'],
    ['Educational Background', 'Doctorate Degree:', 'F25', 'doctorateDegree'],
    ['License and Government Numbers', 'License:', 'F26', 'license'],
    ['License and Government Numbers', 'Registration No.:', 'F27', 'registrationNo'],
    ['License and Government Numbers', 'Registration Date:', 'F28', 'registrationDate'],
    ['License and Government Numbers', 'Valid until:', 'F29', 'validUntil'],
    ['License and Government Numbers', 'SSS:', 'F30', 'sss'],
    ['License and Government Numbers', 'TIN:', 'F31', 'tin'],
    ['License and Government Numbers', 'PhilHealth:', 'F32', 'philHealth'],
    ['License and Government Numbers', 'Pag-IBIG MID:', 'F33', 'pagIbigMid'],
    ['License and Government Numbers', 'Pag-IBIG RTN:', 'F34', 'pagIbigRtn'],
    ['Salary Detail', 'Basic Salary:', 'F35', 'basicSalary'],
    ['Salary Detail', 'Rate per Hour:', 'F36', 'ratePerHour'],
    ['Salary Detail', 'Allowance:', 'F37', 'allowance']
  ];
  let currentGroup = '';
  let groupStart = 0;

  return rows.map(function (row, index) {
    if (row[0] !== currentGroup) {
      currentGroup = row[0];
      groupStart = index;
    }
    return { group: row[0], title: row[1], cell: row[2], key: row[3], groupStart: groupStart };
  });
}

function mergeDataEntryGroupLabels_(sheet, fields, tableStartRow) {
  let startIndex = 0;
  while (startIndex < fields.length) {
    const group = fields[startIndex].group;
    let endIndex = startIndex;
    while (endIndex + 1 < fields.length && fields[endIndex + 1].group === group) endIndex++;

    const startRow = tableStartRow + startIndex + 1;
    const rowCount = endIndex - startIndex + 1;
    sheet.getRange(startRow, 2, rowCount, 2)
      .breakApart()
      .merge()
      .setValue(group)
      .setHorizontalAlignment('center')
      .setVerticalAlignment('middle')
      .setFontWeight('bold')
      .setBackground(getDataEntryGroupColor_(group));

    startIndex = endIndex + 1;
  }
}

function getDataEntryGroupColor_(group) {
  const colors = {
    'Personal Details': '#e2f0d9',
    'Job Details': '#fff2cc',
    'Educational Background': '#ede9fe',
    'License and Government Numbers': '#dbeafe',
    'Salary Detail': '#dcfce7'
  };
  return colors[group] || '#ffffff';
}

function readDataEntrySheet_(sheet) {
  const data = {
    idNumber: sheet.getRange('E5').getValue(),
    pictureUrl: sheet.getRange('K3').getValue()
  };
  getDataEntryTableFields_().forEach(function (field) {
    data[field.key] = sheet.getRange(field.cell).getValue();
  });
  return data;
}

function clearDataEntrySheet_(sheet) {
  sheet.getRange('E5:G5').clearContent();
  sheet.getRange('K3:L4').clearContent();
  sheet.getRange('F10:L37').clearContent();
  sheet.getRange(DATA_ENTRY_SUBMIT_CELL).setValue(false);
}

function setupEmployeeSearchBar_(sheet) {
  const currentSearch = sheet.getRange(SEARCH_INPUT_CELL).getValue();
  sheet.getRange(1, 1, HEADER_ROW, HEADERS.length).breakApart().clearContent().clearFormat();
  sheet.getRange('A1:AI1').mergeAcross()
    .setValue('HRIS 201 File')
    .setBackground('#0f766e')
    .setFontColor('#ffffff')
    .setFontSize(15)
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  sheet.getRange('A2').setValue('Search Name or ID number:');
  sheet.getRange(SEARCH_INPUT_CELL).setValue(currentSearch).setBackground('#ffffff').setBorder(true, true, true, true, true, true).setFontWeight('bold');
  sheet.getRange(SEARCH_STATUS_CELL).setValue('Type in B2 to search');
  sheet.getRange('A2:P2').setBackground('#e9f5f3').setFontColor('#0f766e').setFontWeight('bold').setVerticalAlignment('middle').setFontFamily('Arial');
  setupEmployeeRecordTabs_(sheet);
  sheet.setRowHeight(1, 36);
  sheet.setRowHeight(2, 34);
}

function applyEmployeeRecordSearch_(query) {
  const sheet = ensureEmployeeSheet_();
  const searchTerm = String(query || '').trim().toLowerCase();
  const lastRow = sheet.getLastRow();
  if (lastRow < DATA_START_ROW) return;

  const rowCount = lastRow - HEADER_ROW;
  sheet.showRows(DATA_START_ROW, rowCount);

  if (!searchTerm) {
    sheet.getRange(SEARCH_STATUS_CELL).setValue('Showing all records');
    return;
  }

  const values = sheet.getRange(DATA_START_ROW, 1, rowCount, HEADERS.length).getValues();
  const rowsToHide = [];
  values.forEach(function (row, index) {
    const name = String(row[1] || '').toLowerCase();
    const idNumber = String(row[2] || '').toLowerCase();
    if (!name.includes(searchTerm) && !idNumber.includes(searchTerm)) rowsToHide.push(index + DATA_START_ROW);
  });

  hideRowsInGroups_(sheet, rowsToHide);
  sheet.getRange(SEARCH_STATUS_CELL).setValue((rowCount - rowsToHide.length) + ' record(s) found');
}

function hideRowsInGroups_(sheet, rows) {
  if (!rows.length) return;
  let start = rows[0];
  let count = 1;
  for (let i = 1; i < rows.length; i++) {
    if (rows[i] === rows[i - 1] + 1) {
      count++;
    } else {
      sheet.hideRows(start, count);
      start = rows[i];
      count = 1;
    }
  }
  sheet.hideRows(start, count);
}

function clearEmployeeRecordSearch() {
  const sheet = ensureEmployeeSheet_();
  sheet.getRange(SEARCH_INPUT_CELL).clearContent();
  applyEmployeeRecordSearch_('');
}

function showAllEmployees_() {
  const sheet = ensureEmployeeSheet_();
  const lastRow = sheet.getLastRow();
  if (lastRow >= DATA_START_ROW) {
    sheet.showRows(DATA_START_ROW, lastRow - HEADER_ROW);
  }
  sheet.getRange(SEARCH_INPUT_CELL).clearContent();
  sheet.getRange(SEARCH_STATUS_CELL).setValue('Showing all records');
  SpreadsheetApp.getActiveSpreadsheet().setActiveSheet(sheet);
}

function showLicensedEmployees_() {
  const sheet = ensureEmployeeSheet_();
  const lastRow = sheet.getLastRow();
  if (lastRow < DATA_START_ROW) {
    SpreadsheetApp.getActiveSpreadsheet().setActiveSheet(sheet);
    return;
  }

  const rowCount = lastRow - HEADER_ROW;
  const licenseColumn = HEADERS.indexOf('with/without license') + 1;
  const values = sheet.getRange(DATA_START_ROW, licenseColumn, rowCount, 1).getValues();
  const rowsToHide = [];

  sheet.showRows(DATA_START_ROW, rowCount);
  values.forEach(function (row, index) {
    if (!isLicensedEmployee_({ license: row[0] })) rowsToHide.push(DATA_START_ROW + index);
  });
  hideRowsInGroups_(sheet, rowsToHide);
  sheet.getRange(SEARCH_INPUT_CELL).setValue('Licensed employees');
  sheet.getRange(SEARCH_STATUS_CELL).setValue((rowCount - rowsToHide.length) + ' licensed employee(s) found');
  SpreadsheetApp.getActiveSpreadsheet().setActiveSheet(sheet);
}

function formatEmployeeSheet_(sheet) {
  const lastRow = Math.max(sheet.getLastRow(), HEADER_ROW);
  const lastColumn = HEADERS.length;
  const tableRows = Math.max(lastRow - HEADER_ROW + 1, 1);

  sheet.getRange(1, 1, Math.max(lastRow, DATA_START_ROW), lastColumn).setFontFamily('Arial').setFontSize(10).setVerticalAlignment('middle');
  sheet.getRange(HEADER_ROW, 1, 1, lastColumn)
    .setFontWeight('bold')
    .setFontColor('#ffffff')
    .setBackground('#115e59')
    .setHorizontalAlignment('center')
    .setWrap(true)
    .setBorder(true, true, true, true, true, true, '#0f766e', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(DATA_START_ROW, 1, Math.max(lastRow - HEADER_ROW, 1), lastColumn)
    .setBackground('#ffffff')
    .setFontColor('#1f2937')
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID)
    .setWrap(true);

  for (let row = DATA_START_ROW; row <= lastRow; row++) {
    sheet.getRange(row, 1, 1, lastColumn).setBackground(row % 2 === 0 ? '#f8fafc' : '#ffffff');
  }

  applyColumnWidths_(sheet);
  applyNumberFormatting_(sheet, lastRow);
  applyEmployeeStatusFormatting_(sheet, lastRow);
  const filter = sheet.getFilter();
  if (filter) filter.remove();
  sheet.getRange(HEADER_ROW, 1, tableRows, lastColumn).createFilter();
  hideEmployeeSheetSystemColumns_(sheet);
  sheet.setFrozenRows(HEADER_ROW);
}

function hideEmployeeSheetSystemColumns_(sheet) {
  const profilePictureColumn = HEADERS.indexOf('profile picture') + 1;
  if (profilePictureColumn > 0) {
    sheet.showColumns(profilePictureColumn);
    sheet.setColumnWidth(profilePictureColumn, 220);
  }
}

function applyEmployeeStatusFormatting_(sheet, lastRow) {
  const lastColumn = HEADERS.length;
  const rowCount = Math.max(lastRow - HEADER_ROW, 1);
  const targetRange = sheet.getRange(DATA_START_ROW, 1, rowCount, lastColumn);
  const validUntilRange = sheet.getRange(DATA_START_ROW, 30, rowCount, 1);
  const probationFormula = '=AND($K' + DATA_START_ROW + '<>"",OR(AND($O' + DATA_START_ROW + '="NT",TODAY()<EDATE($K' + DATA_START_ROW + ',6)),AND(OR($O' + DATA_START_ROW + '="T/FT",$O' + DATA_START_ROW + '="T/PT",$O' + DATA_START_ROW + '="Full-Time",$O' + DATA_START_ROW + '="Part-Time"),TODAY()<EDATE($K' + DATA_START_ROW + ',36))))';
  const expiredValidUntilFormula = '=AND($AD' + DATA_START_ROW + '<>"",$AD' + DATA_START_ROW + '<TODAY())';
  const resignedFormula = '=$AH' + DATA_START_ROW + '<>""';
  const existingRules = sheet.getConditionalFormatRules().filter(function (rule) {
    const condition = rule.getBooleanCondition();
    if (!condition) return true;
    const values = condition.getCriteriaValues();
    return !values.some(function (value) {
      const text = String(value || '');
      return text.indexOf('TODAY()<EDATE($K' + DATA_START_ROW) !== -1 ||
        text.indexOf('$AD' + DATA_START_ROW + '<TODAY()') !== -1 ||
        text.indexOf('$AH' + DATA_START_ROW + '<>""') !== -1;
    });
  });

  const probationRule = SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied(probationFormula)
    .setBackground('#fff4b8')
    .setRanges([targetRange])
    .build();
  const expiredValidUntilRule = SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied(expiredValidUntilFormula)
    .setBackground('#ffd6d6')
    .setRanges([validUntilRange])
    .build();
  const resignedRule = SpreadsheetApp.newConditionalFormatRule()
    .whenFormulaSatisfied(resignedFormula)
    .setBackground('#ffd6d6')
    .setRanges([targetRange])
    .build();

  sheet.setConditionalFormatRules(existingRules.concat([probationRule, expiredValidUntilRule, resignedRule]));
}

function buildEmployeeDetailSheet_(employeeSheet) {
  const sheet = getOrCreateSheet_(DASHBOARD_SHEET_NAME);
  const employees = getEmployeeRecords_(employeeSheet);
  const selectedId = sheet.getRange(DASHBOARD_EMPLOYEE_ID_CELL).getValue();
  const employee = employees.find(function (item) {
    return String(item.idNumber || '') === String(selectedId || '');
  }) || employees[employees.length - 1] || {};

  sheet.setFrozenRows(0);
  sheet.setFrozenColumns(0);
  sheet.getRange('A1:P36').breakApart();
  sheet.clear();
  sheet.setHiddenGridlines(true);
  setupTopNav_(sheet);

  sheet.getRange('A3:P4').merge()
    .setValue('HRIS Employee Detail')
    .setBackground('#0f172a')
    .setFontColor('#ffffff')
    .setFontSize(20)
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  sheet.getRange('A5:P5').merge()
    .setValue('Profile snapshot, employment details, compensation, and education summary')
    .setBackground('#e9f5f3')
    .setFontColor('#0f766e')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  sheet.getRange('A7:C14')
    .setBackground('#ecfdf5')
    .setBorder(true, true, true, true, true, true, '#99f6e4', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);
  sheet.getRange('A7:C14').breakApart().clearContent();
  sheet.getRange('A7:C14').merge()
    .setFormula(getEmployeePictureFormula_(employee, 220, 220))
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBackground('#ecfdf5')
    .setBorder(true, true, true, true, true, true, '#99f6e4', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  sheet.getRange('D7:G7').mergeAcross().setValue(employee.name || 'No employee selected').setFontSize(18).setFontWeight('bold');
  sheet.getRange('D8:G8').mergeAcross().setValue(employee.position || '');
  sheet.getRange('D9:G9').mergeAcross().setValue(employee.department || '');
  sheet.getRange('D10:G10').mergeAcross().setValue('ID Number: ' + (employee.idNumber || ''));
  sheet.getRange('D11:G11').mergeAcross().setValue('Contact: ' + (employee.contactNo || ''));
  sheet.getRange('D12:G12').mergeAcross().setValue('Address: ' + (employee.address || ''));

  setEmployeeIdDropdown_(sheet, employees, employee);
  setProfileSection_(sheet, 'A16:D23', 'Personal Detail', '#fff1f2', '#be123c', [
    ['Name:', employee.name || ''],
    ['Date of Birth:', employee.dateOfBirth || ''],
    ['Age:', employee.ageToDate || ''],
    ['Gender:', employee.sex || ''],
    ['Marital Status:', employee.civilStatus || ''],
    ['Address:', employee.address || '']
  ]);
  setProfileSection_(sheet, 'E16:H23', 'Job Detail', '#fef9c3', '#a16207', [
    ['Employment Type:', employee.classification || ''],
    ['Designation:', employee.position || ''],
    ['Employment Date:', employee.employmentDate || ''],
    ['Service Age:', employee.lengthOfService || ''],
    ['Department:', employee.department || ''],
    ['Status:', employee.dateResigned ? 'Resigned' : 'Active']
  ]);
  setProfileSection_(sheet, 'I16:M23', 'Government Numbers', '#e0f2fe', '#0369a1', [
    ['SSS:', employee.sss || ''],
    ['TIN:', employee.tin || ''],
    ['PhilHealth:', employee.philHealth || ''],
    ['Pag-IBIG MID:', employee.pagIbigMid || ''],
    ['Pag-IBIG RTN:', employee.pagIbigRtn || '']
  ]);
  setProfileSection_(sheet, 'A25:F34', 'Educational Background', '#f5f3ff', '#7c3aed', [
    ['Bachelor:', employee.bachelor || ''],
    ["Master's Degree:", employee.mastersDegree || ''],
    ['Doctorate Degree:', employee.doctorateDegree || ''],
    ['License:', normalizeLicenseValue_(employee.license)],
    ['Registration No:', employee.registrationNo || '']
  ]);
  setProfileSection_(sheet, 'G25:M34', 'Salary Detail', '#ecfdf5', '#047857', [
    ['Current Salary:', employee.basicSalary ? formatCurrency_(employee.basicSalary) : ''],
    ['Rate per Hour:', employee.ratePerHour ? formatCurrency_(employee.ratePerHour) : ''],
    ['Allowance:', employee.allowance ? formatCurrency_(employee.allowance) : '']
  ]);

  [105, 115, 112, 118, 108, 118, 112, 118, 108, 118, 108, 118, 126].forEach(function (width, index) {
    sheet.setColumnWidth(index + 1, width);
  });
  for (let row = 1; row <= 36; row++) sheet.setRowHeight(row, 29);
  sheet.setFrozenRows(5);
  sheet.getRange('A1:P36').setFontFamily('Arial').setVerticalAlignment('middle');
  return sheet;
}

function buildDashboardSheet_(employeeSheet) {
  const sheet = getOrCreateSheet_(SUMMARY_SHEET_NAME);
  const employees = getEmployeeRecords_(employeeSheet);
  const totalEmployees = employees.length;
  const activeCount = employees.filter(function (employee) { return !employee.dateResigned; }).length;
  const inactiveCount = employees.filter(function (employee) { return employee.dateResigned; }).length;
  const licensedCount = employees.filter(isLicensedEmployee_).length;
  const fullTimeCount = employees.filter(isFullTimeEmployee_).length;
  const partTimeCount = employees.filter(isPartTimeEmployee_).length;
  const genderRows = summarizeBy_(employees, 'sex');
  const departmentRows = summarizeBy_(employees, 'department');
  const yearRows = summarizeJoinYears_(employees);
  const ageRows = summarizeAgeRanges_(employees);
  const departmentPerformanceRows = summarizeDepartmentPerformance_(employees);

  sheet.setFrozenRows(0);
  sheet.setFrozenColumns(0);
  sheet.getCharts().forEach(function (chart) {
    sheet.removeChart(chart);
  });
  sheet.getRange('A1:Q120').breakApart();
  sheet.clear();
  sheet.setHiddenGridlines(true);
  setupTopNav_(sheet);

  sheet.getRange('A3:P4').merge()
    .setValue('HRIS Dashboard')
    .setBackground('#0f172a')
    .setFontColor('#ffffff')
    .setFontSize(20)
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  sheet.getRange('A5:P5').merge()
    .setValue('Workforce health, demographics, department mix, tenure, and compensation overview')
    .setBackground('#e9f5f3')
    .setFontColor('#0f766e')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  setMetricCard_(sheet, 'A7:B10', 'Total Employees', totalEmployees, '#e0f2fe', '#0369a1');
  setMetricCard_(sheet, 'C7:D10', 'Active', activeCount, '#dcfce7', '#15803d');
  setMetricCard_(sheet, 'E7:F10', 'Inactive', inactiveCount, '#fee2e2', '#b91c1c');
  setMetricCard_(sheet, 'G7:H10', 'Licensed', licensedCount, '#f5f3ff', '#7c3aed');
  setMetricCard_(sheet, 'I7:J10', 'Full-Time', fullTimeCount, '#fff7ed', '#c2410c');
  setMetricCard_(sheet, 'K7:L10', 'Part-Time', partTimeCount, '#fef3c7', '#b45309');
  setMetricCard_(sheet, 'M7:N10', 'Departments', departmentRows.length, '#ecfdf5', '#047857');
  setMetricCard_(sheet, 'O7:P10', 'Latest Join Year', getLatestJoinYear_(employees) || '-', '#f1f5f9', '#334155');
  setupDashboardFilterButtons_(sheet);

  setDashboardSectionTitle_(sheet, 'A12:P12', 'Dashboard Insights');
  setDashboardGenderPie_(sheet, 'A13:E29', genderRows, '#0f766e');
  setDashboardDepartmentPie_(sheet, 'F13:K29', departmentRows, '#2563eb');
  setDashboardLinePanel_(sheet, 'L13:P29', 'Year Joined Trend', yearRows, '#7c3aed');
  setDashboardVisualTable_(sheet, 'A32:F47', 'Age Band Distribution', ageRows, '#c2410c');
  setDashboardVisualTable_(sheet, 'G32:P47', 'Department Performance', departmentPerformanceRows, '#047857');

  setupDashboardYearFilter_(sheet);
  renderDashboardEmployeeTable_(sheet, employees, 'All Employees', 50);

  [100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100].forEach(function (width, index) {
    sheet.setColumnWidth(index + 1, width);
  });
  for (let row = 1; row <= 120; row++) sheet.setRowHeight(row, 28);
  sheet.setRowHeight(3, 34);
  sheet.setRowHeight(4, 34);
  sheet.setRowHeight(5, 30);
  sheet.setRowHeight(12, 30);
  sheet.setRowHeight(50, 30);
  sheet.getRange('A7:P10').setFontSize(11);
  sheet.getRange('A13:P120').setFontSize(9);
  sheet.setFrozenRows(5);
  sheet.getRange('A1:P120').setFontFamily('Arial').setVerticalAlignment('middle');
  return sheet;
}

function setupTopNav_(sheet) {
  const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  const employeeSheet = getOrCreateSheet_(SHEET_NAME);
  const employeeDetailSheet = getOrCreateSheet_(DASHBOARD_SHEET_NAME);
  const dashboardSheet = getOrCreateSheet_(SUMMARY_SHEET_NAME);
  const dataEntrySheet = getOrCreateSheet_(DATA_ENTRY_SHEET_NAME);
  const profileSheet = spreadsheet.getSheetByName(PROFILE_SHEET_NAME) || spreadsheet.insertSheet(PROFILE_SHEET_NAME);
  const items = [
    { range: 'A1:C1', label: 'Employee Detail', color: '#0f766e', link: '#gid=' + employeeDetailSheet.getSheetId() },
    { range: 'D1:F1', label: 'Dashboard', color: '#2563eb', link: '#gid=' + dashboardSheet.getSheetId() },
    { range: 'G1:I1', label: 'Data Entry', color: '#7c3aed', link: '#gid=' + dataEntrySheet.getSheetId() },
    { range: 'J1:M1', label: 'Profile', color: '#0e7490', link: '#gid=' + profileSheet.getSheetId() },
    { range: 'N1:P1', label: 'Employee Record', color: '#be123c', link: '#gid=' + employeeSheet.getSheetId() }
  ];

  sheet.getRange('A1:P1').breakApart().clearContent().clearFormat();
  drawSectionTabs_(sheet, items);
  sheet.setRowHeight(1, 36);
}

function setupEmployeeRecordTabs_(sheet) {
  const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  const employeeSheet = getOrCreateSheet_(SHEET_NAME);
  const employeeDetailSheet = getOrCreateSheet_(DASHBOARD_SHEET_NAME);
  const dashboardSheet = getOrCreateSheet_(SUMMARY_SHEET_NAME);
  const dataEntrySheet = getOrCreateSheet_(DATA_ENTRY_SHEET_NAME);
  const profileSheet = spreadsheet.getSheetByName(PROFILE_SHEET_NAME) || spreadsheet.insertSheet(PROFILE_SHEET_NAME);
  const items = getSectionTabItems_(employeeDetailSheet, dashboardSheet, dataEntrySheet, profileSheet, employeeSheet);

  drawSectionTabs_(sheet, items);
}

function getSectionTabItems_(employeeDetailSheet, dashboardSheet, dataEntrySheet, profileSheet, employeeSheet) {
  return [
    { range: 'D2:F2', label: 'Employee Detail', color: '#0f766e', link: '#gid=' + employeeDetailSheet.getSheetId() },
    { range: 'G2:I2', label: 'Dashboard', color: '#2563eb', link: '#gid=' + dashboardSheet.getSheetId() },
    { range: 'J2:L2', label: 'Data Entry', color: '#7c3aed', link: '#gid=' + dataEntrySheet.getSheetId() },
    { range: 'M2:O2', label: 'Profile', color: '#0e7490', link: '#gid=' + profileSheet.getSheetId() },
    { range: 'P2:R2', label: 'Employee Record', color: '#be123c', link: '#gid=' + employeeSheet.getSheetId() }
  ];
}

function drawSectionTabs_(sheet, items) {
  sheet.getRangeList(items.map(function (item) { return item.range; })).breakApart().clearContent().clearFormat();
  items.forEach(function (item) {
    sheet.getRange(item.range)
      .mergeAcross()
      .setFormula('=HYPERLINK("' + item.link + '","' + item.label + '")')
      .setBackground(item.color)
      .setFontColor('#ffffff')
      .setFontWeight('bold')
      .setHorizontalAlignment('center')
      .setVerticalAlignment('middle')
      .setBorder(true, true, true, true, false, false, '#ffffff', SpreadsheetApp.BorderStyle.SOLID);
  });
}

function setEmployeeIdDropdown_(sheet, employees, employee) {
  const ids = employees.map(function (item) { return String(item.idNumber || '').trim(); })
    .filter(function (id, index, allIds) { return id && allIds.indexOf(id) === index; });

  sheet.getRange('H7:M10').breakApart().clearContent().clearFormat();
  sheet.getRange('H7:M7').mergeAcross()
    .setValue('Employee ID Search')
    .setBackground('#0f766e')
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#0f766e', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  sheet.getRange('H8:I9').merge()
    .setValue('Select ID')
    .setBackground('#e9f5f3')
    .setFontColor('#0f766e')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#99d6cd', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange('J8:M9').merge()
    .setBackground('#ffffff')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#334155', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  const selector = sheet.getRange(DASHBOARD_EMPLOYEE_ID_CELL);
  selector.setValue(employee.idNumber || '')
    .setFontWeight('bold')
    .setFontSize(12)
    .setFontColor('#0f172a')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  sheet.getRange('H10:M10').merge()
    .setValue(ids.length ? 'Choose an ID to view another employee profile' : 'No employee IDs available')
    .setBackground('#f8fafc')
    .setFontColor('#64748b')
    .setFontSize(9)
    .setFontStyle('italic')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);

  if (ids.length) {
    selector.setDataValidation(SpreadsheetApp.newDataValidation().requireValueInList(ids, true).setAllowInvalid(false).build());
  } else {
    selector.clearDataValidations();
  }
}

function setProfileSection_(sheet, rangeA1, title, color, accent, rows) {
  const range = sheet.getRange(rangeA1);
  const startRow = range.getRow();
  const startColumn = range.getColumn();
  const rowCount = range.getNumRows();
  const columnCount = range.getNumColumns();
  const valueStartColumn = startColumn + Math.min(2, columnCount - 1);
  const valueColumnCount = columnCount - Math.min(2, columnCount - 1);

  range.breakApart().clearContent().setBackground(color).setBorder(true, true, true, true, true, true, '#e2e8f0', SpreadsheetApp.BorderStyle.SOLID);
  sheet.getRange(startRow, startColumn, 1, columnCount).mergeAcross()
    .setValue(title)
    .setBackground(accent)
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  rows.forEach(function (row, index) {
    const targetRow = startRow + index + 2;
    if (targetRow > startRow + rowCount - 1) return;
    sheet.getRange(targetRow, startColumn, 1, Math.min(2, columnCount - 1)).mergeAcross().setValue(row[0]).setFontWeight('bold');
    sheet.getRange(targetRow, valueStartColumn, 1, valueColumnCount).mergeAcross().setValue(row[1]).setWrap(true);
  });
}

function setMetricCard_(sheet, rangeA1, title, value, background, accent, link) {
  const displayText = title + '\n' + value + (link ? '\nView all' : '');
  const range = sheet.getRange(rangeA1).breakApart().merge();
  if (link) {
    range.setRichTextValue(
      SpreadsheetApp.newRichTextValue()
        .setText(displayText)
        .setLinkUrl(link)
        .build()
    );
  } else {
    range.setValue(displayText);
  }

  range
    .setBackground(background)
    .setFontColor(accent)
    .setFontWeight('bold')
    .setFontSize(10)
    .setFontLine(link ? 'underline' : 'none')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setWrap(true)
    .setBorder(true, true, true, true, true, true, accent, SpreadsheetApp.BorderStyle.SOLID_MEDIUM);
}

function showDashboardEmployeeTable_(sheet, employees, title) {
  renderDashboardEmployeeTable_(sheet, employees, title, 50);
  SpreadsheetApp.flush();
  SpreadsheetApp.getActiveSpreadsheet().toast(title + ': ' + employees.length + ' record(s)', 'Dashboard', 3);
}

function setupDashboardYearFilter_(sheet) {
  sheet.getRange('M49')
    .setValue('Year Filter')
    .setBackground('#e9f5f3')
    .setFontColor('#0f766e')
    .setFontWeight('bold')
    .setHorizontalAlignment('right');
  sheet.getRange(DASHBOARD_YEAR_FILTER_CELL)
    .setBackground('#ffffff')
    .setFontColor('#0f172a')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setBorder(true, true, true, true, true, true, '#0f766e', SpreadsheetApp.BorderStyle.SOLID);
  sheet.getRange('O49:P49').mergeAcross()
    .setValue('Type year, e.g. 2020')
    .setFontColor('#64748b')
    .setFontStyle('italic');
}

function setupDashboardFilterButtons_(sheet) {
  sheet.getRange('A11:Q11')
    .breakApart()
    .clearContent()
    .clearFormat()
    .clearDataValidations();

  const buttons = [
    { cell: DASHBOARD_TOTAL_FILTER_CELL, color: '#0369a1' },
    { cell: DASHBOARD_ACTIVE_FILTER_CELL, color: '#15803d' },
    { cell: DASHBOARD_INACTIVE_FILTER_CELL, color: '#b91c1c' },
    { cell: DASHBOARD_LICENSED_FILTER_CELL, color: '#7c3aed' },
    { cell: DASHBOARD_FULL_TIME_FILTER_CELL, color: '#c2410c' },
    { cell: DASHBOARD_PART_TIME_FILTER_CELL, color: '#b45309' },
    { cell: DASHBOARD_DEPARTMENT_FILTER_CELL, color: '#047857' },
    { cell: DASHBOARD_LATEST_YEAR_FILTER_CELL, color: '#334155' }
  ];

  sheet.getRange('A11:P11')
    .setBackground('#ffffff')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  buttons.forEach(function (button) {
    sheet.getRange(button.cell)
      .insertCheckboxes()
      .setValue(false)
      .setHorizontalAlignment('center')
      .setVerticalAlignment('middle')
      .setBackground('#ffffff');
  });
}

function handleDashboardFilterEdit_(sheet, cellA1) {
  const employees = getEmployeeRecords_(ensureEmployeeSheet_());

  if (cellA1 === DASHBOARD_TOTAL_FILTER_CELL) {
    showDashboardEmployeeTable_(sheet, employees, 'All Employees');
    return true;
  }

  if (cellA1 === DASHBOARD_ACTIVE_FILTER_CELL) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return !employee.dateResigned;
    }), 'Active Employees');
    return true;
  }

  if (cellA1 === DASHBOARD_LICENSED_FILTER_CELL) {
    showDashboardEmployeeTable_(sheet, employees.filter(isLicensedEmployee_), 'Licensed Employees');
    return true;
  }

  if (cellA1 === DASHBOARD_FULL_TIME_FILTER_CELL) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return isFullTimeEmployee_(employee);
    }), 'Full-Time Employees');
    return true;
  }

  if (cellA1 === DASHBOARD_PART_TIME_FILTER_CELL) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return isPartTimeEmployee_(employee);
    }), 'Part-Time Employees');
    return true;
  }

  if (cellA1 === DASHBOARD_DEPARTMENT_FILTER_CELL) {
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      return String(employee.department || '').trim();
    }), 'Employees With Department');
    return true;
  }

  if (cellA1 === DASHBOARD_LATEST_YEAR_FILTER_CELL) {
    const latestYear = getLatestJoinYear_(employees);
    showDashboardEmployeeTable_(sheet, employees.filter(function (employee) {
      const employmentDate = parseFormDate_(employee.employmentDate);
      return employmentDate && employmentDate.getFullYear() === latestYear;
    }), 'Employees Joined in ' + (latestYear || 'Latest Year'));
    return true;
  }

  return false;
}

function getDashboardFilterCells_() {
  return [
    DASHBOARD_TOTAL_FILTER_CELL,
    DASHBOARD_ACTIVE_FILTER_CELL,
    DASHBOARD_INACTIVE_FILTER_CELL,
    DASHBOARD_LICENSED_FILTER_CELL,
    DASHBOARD_FULL_TIME_FILTER_CELL,
    DASHBOARD_PART_TIME_FILTER_CELL,
    DASHBOARD_DEPARTMENT_FILTER_CELL,
    DASHBOARD_LATEST_YEAR_FILTER_CELL
  ];
}

function isDashboardFilterCell_(cellA1) {
  return getDashboardFilterCells_().indexOf(cellA1) !== -1;
}

function hasCheckedDashboardFilter_(sheet) {
  return getDashboardFilterCells_().some(function (cellA1) {
    return sheet.getRange(cellA1).getValue() === true;
  });
}

function applyDashboardCombinedFilters_(sheet) {
  const employees = getEmployeeRecords_(ensureEmployeeSheet_());
  const filters = [];
  const titles = [];

  if (sheet.getRange(DASHBOARD_TOTAL_FILTER_CELL).getValue() === true) {
    showDashboardEmployeeTable_(sheet, employees, 'All Employees');
    return;
  }

  if (sheet.getRange(DASHBOARD_ACTIVE_FILTER_CELL).getValue() === true) {
    filters.push(function (employee) { return !employee.dateResigned; });
    titles.push('Active');
  }

  if (sheet.getRange(DASHBOARD_INACTIVE_FILTER_CELL).getValue() === true) {
    filters.push(function (employee) { return employee.dateResigned; });
    titles.push('Inactive');
  }

  if (sheet.getRange(DASHBOARD_LICENSED_FILTER_CELL).getValue() === true) {
    filters.push(isLicensedEmployee_);
    titles.push('Licensed');
  }

  if (sheet.getRange(DASHBOARD_FULL_TIME_FILTER_CELL).getValue() === true) {
    filters.push(function (employee) { return isFullTimeEmployee_(employee); });
    titles.push('Full-Time');
  }

  if (sheet.getRange(DASHBOARD_PART_TIME_FILTER_CELL).getValue() === true) {
    filters.push(function (employee) { return isPartTimeEmployee_(employee); });
    titles.push('Part-Time');
  }

  if (sheet.getRange(DASHBOARD_DEPARTMENT_FILTER_CELL).getValue() === true) {
    const departmentRows = summarizeBy_(employees, 'department');
    renderDashboardDepartmentCountTable_(sheet, departmentRows);
    SpreadsheetApp.getActiveSpreadsheet().toast('Departments: ' + departmentRows.length + ' department(s)', 'Dashboard', 3);
    return;
  }

  if (sheet.getRange(DASHBOARD_LATEST_YEAR_FILTER_CELL).getValue() === true) {
    const latestYear = getLatestJoinYear_(employees);
    filters.push(function (employee) {
      const employmentDate = parseFormDate_(employee.employmentDate);
      return employmentDate && employmentDate.getFullYear() === latestYear;
    });
    titles.push('Joined ' + (latestYear || 'Latest Year'));
  }

  const filteredEmployees = employees.filter(function (employee) {
    return filters.every(function (filter) {
      return filter(employee);
    });
  });
  showDashboardEmployeeTable_(sheet, filteredEmployees, titles.join(' + ') + ' Employees');
}

function applyDashboardYearFilter_(sheet, yearValue) {
  const year = String(yearValue || '').trim();
  const employees = getEmployeeRecords_(ensureEmployeeSheet_());
  if (!year) {
    showDashboardEmployeeTable_(sheet, employees, 'All Employees');
    return;
  }

  const filteredEmployees = employees.filter(function (employee) {
    return String(getEmployeeJoinYear_(employee) || '') === year;
  });
  showDashboardEmployeeTable_(sheet, filteredEmployees, 'Employees Joined in ' + year);
}

function getEmployeeJoinYear_(employee) {
  const joinDate = getEmployeeJoinDate_(employee);
  return joinDate ? joinDate.getFullYear() : '';
}

function getEmployeeJoinDate_(employee) {
  return parseFormDate_(employee.employmentDate) || parseFormDate_(employee.timestamp);
}

function renderDashboardEmployeeTable_(sheet, employees, title, startRow) {
  startRow = startRow || 50;
  const headerRow = startRow + 1;
  const dataStartRow = headerRow + 1;
  const maxRows = 106;
  const tableHeaders = [
    '#',
    'ID Number',
    'Name',
    'Position',
    'Department',
    'Class',
    'License',
    'Employment Date',
    'Contact',
    'Address',
    'Status',
    'Basic Salary',
    'Allowance',
    'Join Year'
  ];

  sheet.getRange(startRow, 1, maxRows + 2, tableHeaders.length)
    .breakApart()
    .clearContent()
    .clearFormat()
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(startRow, 1, 1, tableHeaders.length).mergeAcross()
    .setValue(title + ' (' + employees.length + ')')
    .setBackground('#0f172a')
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setFontSize(12)
    .setHorizontalAlignment('center');

  sheet.getRange(headerRow, 1, 1, tableHeaders.length)
    .setValues([tableHeaders])
    .setBackground('#115e59')
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setWrap(true);

  const rows = employees.slice(0, maxRows).map(function (employee, index) {
    return [
      index + 1,
      employee.idNumber || '',
      employee.name || '',
      employee.position || '',
      employee.department || '',
      employee.classification || '',
      normalizeLicenseValue_(employee.license),
      employee.employmentDate || '',
      employee.contactNo || '',
      employee.address || '',
      employee.dateResigned ? 'Resigned' : 'Active',
      employee.basicSalary || '',
      employee.allowance || '',
      getEmployeeJoinYear_(employee) || ''
    ];
  });

  if (rows.length) {
    sheet.getRange(dataStartRow, 1, rows.length, tableHeaders.length)
      .setValues(rows)
      .setFontColor('#1f2937')
      .setWrap(true)
      .setVerticalAlignment('middle');

    for (let row = dataStartRow; row < dataStartRow + rows.length; row++) {
      sheet.getRange(row, 1, 1, tableHeaders.length)
        .setBackground(row % 2 === 0 ? '#f8fafc' : '#ffffff');
    }
  } else {
    sheet.getRange(dataStartRow, 1, 1, tableHeaders.length).mergeAcross()
      .setValue('No employees found')
      .setBackground('#ffffff')
      .setFontColor('#64748b')
      .setHorizontalAlignment('center');
  }

  sheet.getRange(startRow, 1, Math.max(rows.length + 2, 3), tableHeaders.length)
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);
}

function renderDashboardDepartmentCountTable_(sheet, departmentRows) {
  const startRow = 50;
  const headerRow = startRow + 1;
  const dataStartRow = headerRow + 1;
  const headers = ['#', 'Department', 'Total Employees', 'Full-Time', 'Part-Time', 'Percent'];
  const employees = getEmployeeRecords_(ensureEmployeeSheet_());
  const total = departmentRows.reduce(function (sum, row) {
    return sum + Number(row.count || 0);
  }, 0);
  const rows = departmentRows.map(function (row, index) {
    const departmentName = row.label || 'Blank';
    const departmentEmployees = employees.filter(function (employee) {
      return (String(employee.department || '').trim() || 'Blank') === departmentName;
    });
    return [
      index + 1,
      departmentName,
      Number(row.count || 0),
      departmentEmployees.filter(isFullTimeEmployee_).length,
      departmentEmployees.filter(isPartTimeEmployee_).length,
      total ? Math.round((Number(row.count || 0) / total) * 100) + '%' : '0%'
    ];
  });

  sheet.getRange(startRow, 1, 108, 14)
    .breakApart()
    .clearContent()
    .clearFormat()
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(startRow, 1, 1, headers.length).mergeAcross()
    .setValue('Department Employee Count (' + total + ')')
    .setBackground('#0f172a')
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setFontSize(12)
    .setHorizontalAlignment('center');

  sheet.getRange(headerRow, 1, 1, headers.length)
    .setValues([headers])
    .setBackground('#115e59')
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  if (rows.length) {
    sheet.getRange(dataStartRow, 1, rows.length, headers.length)
      .setValues(rows)
      .setFontColor('#1f2937')
      .setVerticalAlignment('middle');
    for (let row = dataStartRow; row < dataStartRow + rows.length; row++) {
      sheet.getRange(row, 1, 1, headers.length)
        .setBackground(row % 2 === 0 ? '#f8fafc' : '#ffffff');
    }
  } else {
    sheet.getRange(dataStartRow, 1, 1, headers.length).mergeAcross()
      .setValue('No departments found')
      .setBackground('#ffffff')
      .setFontColor('#64748b')
      .setHorizontalAlignment('center');
  }

  sheet.getRange(startRow, 1, Math.max(rows.length + 2, 3), headers.length)
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);
}

function setDashboardSectionTitle_(sheet, rangeA1, title) {
  sheet.getRange(rangeA1).breakApart().merge()
    .setValue(title)
    .setBackground('#0f172a')
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle')
    .setBorder(true, true, true, true, true, true, '#0f172a', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);
}

function setDashboardGenderPie_(sheet, rangeA1, rows, accent) {
  setDashboardPiePanel_(sheet, rangeA1, 'Gender Mix', rows, accent);
}

function setDashboardDepartmentPie_(sheet, rangeA1, rows, accent) {
  setDashboardPiePanel_(sheet, rangeA1, 'Employees by Department', rows, accent);
}

function setDashboardPiePanel_(sheet, rangeA1, title, rows, accent) {
  const range = sheet.getRange(rangeA1);
  const startRow = range.getRow();
  const startColumn = range.getColumn();
  const rowCount = range.getNumRows();
  const columnCount = range.getNumColumns();
  const pieRows = rows.filter(function (item) { return Number(item.count || 0) > 0; });
  const colors = ['#0f766e', '#2563eb', '#7c3aed', '#c2410c', '#047857', '#be123c'];

  range.breakApart()
    .clearContent()
    .clearFormat()
    .setBackground('#ffffff')
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(startRow, startColumn, 1, columnCount).mergeAcross()
    .setValue(title)
    .setBackground(accent)
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  if (!pieRows.length) {
    sheet.getRange(startRow + 1, startColumn, rowCount - 1, columnCount).merge()
      .setValue('No data')
      .setFontColor('#64748b')
      .setHorizontalAlignment('center')
      .setVerticalAlignment('middle');
    return;
  }

  const pieFormula = '=IMAGE("' + escapeFormulaText_(buildQuickChartPieUrl_(pieRows, colors)) + '",4,300,330)';
  sheet.getRange(startRow + 2, startColumn, Math.max(1, rowCount - 3), Math.max(1, columnCount - 1)).merge()
    .setFormula(pieFormula)
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  pieRows.slice(0, rowCount - 3).forEach(function (item, index) {
    const targetRow = startRow + 2 + index;
    const total = pieRows.reduce(function (sum, row) { return sum + Number(row.count || 0); }, 0);
    const percent = total ? Math.round((Number(item.count || 0) / total) * 100) + '%' : '0%';
    sheet.getRange(targetRow, startColumn + columnCount - 1)
      .setValue((item.label || 'Blank') + ': ' + Number(item.count || 0) + ' (' + percent + ')')
      .setFontColor(colors[index % colors.length])
      .setFontWeight('bold')
      .setFontSize(14)
      .setHorizontalAlignment('left')
      .setWrap(true);
  });
}

function buildQuickChartPieUrl_(rows, colors) {
  const total = rows.reduce(function (sum, item) { return sum + Number(item.count || 0); }, 0);
  const labels = rows.map(function (item) {
    const percent = total ? Math.round((Number(item.count || 0) / total) * 100) : 0;
    return String(item.label || 'Blank') + ' (' + percent + '%)';
  });
  const values = rows.map(function (item) {
    return total ? Math.round((Number(item.count || 0) / total) * 100) : 0;
  });
  const chartConfig = {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: values,
        backgroundColor: colors.slice(0, rows.length)
      }]
    },
    options: {
      plugins: {
        datalabels: {
          color: '#ffffff',
          font: {
            weight: 'bold',
            size: 30
          },
          formatter: 'function(value) { return value + "%"; }'
        },
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            boxWidth: 68,
            padding: 44,
            fontSize: 80,
            fontStyle: 'bold',
            fontColor: '#111827',
            font: {
              size: 80,
              weight: 'bold'
            }
          }
        }
      }
    }
  };

  return 'https://quickchart.io/chart?width=820&height=680&devicePixelRatio=2&backgroundColor=white&c=' +
    encodeURIComponent(JSON.stringify(chartConfig));
}

function setDashboardLinePanel_(sheet, rangeA1, title, rows, accent) {
  const range = sheet.getRange(rangeA1);
  const startRow = range.getRow();
  const startColumn = range.getColumn();
  const rowCount = range.getNumRows();
  const columnCount = range.getNumColumns();
  const lineRows = rows.filter(function (item) {
    return String(item.label || '').trim() && String(item.label || '').trim() !== 'Blank';
  });
  const chartRows = buildYearTrendRows_(lineRows);

  range.breakApart()
    .clearContent()
    .clearFormat()
    .setBackground('#ffffff')
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(startRow, startColumn, 1, columnCount).mergeAcross()
    .setValue(title)
    .setBackground(accent)
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  if (!chartRows.length) {
    sheet.getRange(startRow + 1, startColumn, rowCount - 1, columnCount).merge()
      .setValue('No year joined data')
      .setFontColor('#64748b')
      .setHorizontalAlignment('center')
      .setVerticalAlignment('middle');
    return;
  }

  const lineFormula = '=IMAGE("' + escapeFormulaText_(buildQuickChartLineUrl_(chartRows, accent)) + '",4,300,470)';
  sheet.getRange(startRow + 2, startColumn, Math.max(1, rowCount - 3), columnCount).merge()
    .setFormula(lineFormula)
    .setHorizontalAlignment('center')
    .setVerticalAlignment('middle');

  const total = chartRows.reduce(function (sum, item) { return sum + Number(item.count || 0); }, 0);
  sheet.getRange(startRow + rowCount - 1, startColumn, 1, columnCount).mergeAcross()
    .setValue('Total joined: ' + total)
    .setBackground('#f5f3ff')
    .setFontColor(accent)
    .setFontWeight('bold')
    .setFontSize(14)
    .setHorizontalAlignment('center');
}

function buildYearTrendRows_(rows) {
  const currentYear = new Date().getFullYear();
  const startYear = currentYear - 10;
  const countsByYear = {};

  rows.forEach(function (item) {
    const year = Number(item.label);
    if (!year) return;
    countsByYear[year] = (countsByYear[year] || 0) + Number(item.count || 0);
  });

  const output = [];
  for (let year = startYear; year <= currentYear; year++) {
    output.push({ label: String(year), count: countsByYear[year] || 0 });
  }
  return output;
}

function buildQuickChartLineUrl_(rows, accent) {
  const chartRows = rows;
  const labels = chartRows.map(function (item) { return String(item.label || 'Blank'); });
  const counts = chartRows.map(function (item) { return Number(item.count || 0); });
  const chartConfig = {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Employees',
        data: counts,
        count: counts,
        borderColor: accent,
        backgroundColor: 'rgba(124,58,237,0.16)',
        borderWidth: 6,
        fill: true,
        tension: 0.3,
        pointRadius: 8,
        pointHoverRadius: 10,
        pointBackgroundColor: accent
      }]
    },
    options: {
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: {
            boxWidth: 30,
            padding: 18,
            fontSize: 28,
            fontStyle: 'bold',
            fontColor: '#111827',
            font: {
              size: 28,
              weight: 'bold'
            }
          }
        },
        datalabels: {
          align: 'top',
          anchor: 'end',
          color: accent,
          font: {
            weight: 'bold',
            size: 20
          },
          formatter: 'function(value) { return value; }'
        }
      },
      layout: {
        padding: {
          top: 70,
          right: 36,
          bottom: 28,
          left: 20
        }
      },
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true,
            min: 0,
            precision: 0,
            fontSize: 22,
            fontColor: '#111827'
          },
          gridLines: {
            color: 'rgba(15,23,42,0.08)'
          }
        }],
        xAxes: [{
          ticks: {
            fontSize: 22,
            fontColor: '#111827'
          },
          gridLines: {
            display: false
          }
        }]
      }
    }
  };

  return 'https://quickchart.io/chart?width=980&height=620&devicePixelRatio=2&backgroundColor=white&cacheBust=' +
    new Date().getTime() + '&c=' + encodeURIComponent(JSON.stringify(chartConfig));
}

function setDashboardVisualTable_(sheet, rangeA1, title, rows, accent) {
  const range = sheet.getRange(rangeA1);
  const startRow = range.getRow();
  const startColumn = range.getColumn();
  const rowCount = range.getNumRows();
  const columnCount = range.getNumColumns();
  const countColumn = startColumn + columnCount - 1;
  const labelColumnCount = Math.max(1, countColumn - startColumn);
  const visualRows = rows.length ? rows : [{ label: 'No data', count: 0 }];

  range.breakApart()
    .clearContent()
    .clearFormat()
    .setBackground('#ffffff')
    .setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);

  sheet.getRange(startRow, startColumn, 1, columnCount).mergeAcross()
    .setValue(title)
    .setBackground(accent)
    .setFontColor('#ffffff')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  sheet.getRange(startRow + 1, startColumn, 1, labelColumnCount).mergeAcross()
    .setValue('Category')
    .setBackground('#f8fafc')
    .setFontColor('#334155')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');
  sheet.getRange(startRow + 1, countColumn)
    .setValue('Count')
    .setBackground('#f8fafc')
    .setFontColor('#334155')
    .setFontWeight('bold')
    .setHorizontalAlignment('center');

  visualRows.slice(0, rowCount - 2).forEach(function (row, index) {
    const targetRow = startRow + index + 2;
    const count = Number(row.count || 0);
    sheet.getRange(targetRow, startColumn, 1, labelColumnCount).mergeAcross()
      .setValue(row.label || 'Blank')
      .setHorizontalAlignment('left');
    sheet.getRange(targetRow, countColumn)
      .setValue(count)
      .setHorizontalAlignment('center');
  });

  sheet.getRange(startRow + 2, startColumn, Math.max(rowCount - 2, 1), columnCount)
    .setVerticalAlignment('middle')
    .setWrap(true);
}

function setSummaryTable_(sheet, rangeA1, title, rows) {
  const range = sheet.getRange(rangeA1);
  const startRow = range.getRow();
  const startColumn = range.getColumn();
  const rowCount = range.getNumRows();
  const columnCount = range.getNumColumns();
  const headers = columnCount >= 4 ? ['Category', 'Count', 'Percent', 'Details'] : ['Category', 'Count', 'Percent'];
  const output = [padRow_(headers, columnCount)];

  range.breakApart().clearContent().setBackground('#ffffff').setBorder(true, true, true, true, true, true, '#dbe3ef', SpreadsheetApp.BorderStyle.SOLID);
  sheet.getRange(startRow, startColumn, 1, columnCount).mergeAcross().setValue(title).setBackground('#1d4ed8').setFontColor('#ffffff').setFontWeight('bold').setHorizontalAlignment('center');

  rows.slice(0, rowCount - 2).forEach(function (row) {
    output.push(padRow_([row.label || '', row.count || 0, row.percent || '', row.details || ''], columnCount));
  });
  while (output.length < rowCount - 1) output.push(new Array(columnCount).fill(''));
  sheet.getRange(startRow + 1, startColumn, rowCount - 1, columnCount).setValues(output);
  sheet.getRange(startRow + 1, startColumn, 1, columnCount).setBackground('#eff6ff').setFontColor('#1d4ed8').setFontWeight('bold').setHorizontalAlignment('center');
}

function padRow_(values, columnCount) {
  const row = values.slice(0, columnCount);
  while (row.length < columnCount) row.push('');
  return row;
}

function getEmployeeRecords_(sheet) {
  const lastRow = sheet.getLastRow();
  if (lastRow < DATA_START_ROW) return [];

  const profilePictures = getProfilePictureMap_();
  return sheet.getRange(DATA_START_ROW, 1, lastRow - HEADER_ROW, HEADERS.length)
    .getValues()
    .filter(function (row) { return row[1] || row[2]; })
    .map(function (row) {
      const idNumber = row[2];
      return {
        timestamp: row[0],
        name: row[1],
        idNumber: idNumber,
        accountNo: row[3],
        sex: row[4],
        civilStatus: row[5],
        address: row[6],
        contactNo: row[7],
        dateOfBirth: row[8],
        ageToDate: row[9],
        employmentDate: row[10],
        lengthOfService: row[11],
        position: row[12],
        department: row[13],
        classification: row[14],
        sss: row[17],
        tin: row[18],
        philHealth: row[19],
        pagIbigMid: row[20],
        pagIbigRtn: row[21],
        bachelor: row[22],
        mastersDegree: row[23],
        doctorateDegree: row[24],
        license: normalizeLicenseValue_(row[25]),
        registrationNo: row[27],
        ratePerHour: row[30],
        basicSalary: row[31],
        allowance: row[32],
        dateResigned: row[33],
        pictureUrl: row[35] || profilePictures[String(idNumber || '').trim()] || ''
      };
    });
}

function updateComputedDateFields_(sheet, range) {
  const startRow = range.getRow();
  const endRow = startRow + range.getNumRows() - 1;
  const startColumn = range.getColumn();
  const endColumn = startColumn + range.getNumColumns() - 1;
  const touchesDateOfBirth = startColumn <= 9 && endColumn >= 9;
  const touchesEmploymentDate = startColumn <= 11 && endColumn >= 11;
  if (endRow < DATA_START_ROW || (!touchesDateOfBirth && !touchesEmploymentDate)) return;

  const firstDataRow = Math.max(startRow, DATA_START_ROW);
  const rowCount = endRow - firstDataRow + 1;
  const today = new Date();

  if (touchesDateOfBirth) {
    const ages = sheet.getRange(firstDataRow, 9, rowCount, 1).getValues().map(function (row) {
      return [formatAge_(row[0], today)];
    });
    sheet.getRange(firstDataRow, 10, rowCount, 1).setValues(ages);
  }

  if (touchesEmploymentDate) {
    const lengths = sheet.getRange(firstDataRow, 11, rowCount, 1).getValues().map(function (row) {
      return [formatDateDifference_(row[0], today)];
    });
    sheet.getRange(firstDataRow, 12, rowCount, 1).setValues(lengths);
  }
}

function formatAge_(dateOfBirthValue, endDate) {
  const dateOfBirth = parseFormDate_(dateOfBirthValue);
  if (!dateOfBirth || dateOfBirth > endDate) return '';

  let age = endDate.getFullYear() - dateOfBirth.getFullYear();
  const birthdayThisYear = new Date(endDate.getFullYear(), dateOfBirth.getMonth(), dateOfBirth.getDate());
  if (endDate < birthdayThisYear) age--;
  return age + ' y/o';
}

function formatDateDifference_(startDateValue, endDate) {
  const startDate = parseFormDate_(startDateValue);
  if (!startDate || startDate > endDate) return '';

  let years = endDate.getFullYear() - startDate.getFullYear();
  let months = endDate.getMonth() - startDate.getMonth();
  let days = endDate.getDate() - startDate.getDate();

  if (days < 0) {
    months--;
    days += new Date(endDate.getFullYear(), endDate.getMonth(), 0).getDate();
  }
  if (months < 0) {
    years--;
    months += 12;
  }
  return years + ' year(s), ' + months + ' month(s), ' + days + ' day(s)';
}

function parseFormDate_(value) {
  if (!value) return null;
  if (Object.prototype.toString.call(value) === '[object Date]') {
    return new Date(value.getFullYear(), value.getMonth(), value.getDate());
  }

  if (typeof value === 'number') {
    const sheetsEpoch = new Date(1899, 11, 30);
    return new Date(sheetsEpoch.getFullYear(), sheetsEpoch.getMonth(), sheetsEpoch.getDate() + value);
  }

  const text = String(value).trim();
  let match = text.match(/^(\d{4})[-/](\d{1,2})[-/](\d{1,2})$/);
  if (match) {
    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  }

  match = text.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{2,4})$/);
  if (match) {
    const rawYear = Number(match[3]);
    const year = rawYear < 100 ? 2000 + rawYear : rawYear;
    return new Date(year, Number(match[1]) - 1, Number(match[2]));
  }

  const parsedDate = new Date(text);
  if (!isNaN(parsedDate.getTime())) {
    return new Date(parsedDate.getFullYear(), parsedDate.getMonth(), parsedDate.getDate());
  }

  return null;
}

function summarizeBy_(employees, key) {
  const counts = {};
  employees.forEach(function (employee) {
    const label = String(employee[key] || 'Blank').trim() || 'Blank';
    counts[label] = (counts[label] || 0) + 1;
  });
  return Object.keys(counts).sort().map(function (label) {
    const count = counts[label];
    return { label: label, count: count, percent: employees.length ? Math.round((count / employees.length) * 100) + '%' : '0%' };
  });
}

function summarizeJoinYears_(employees) {
  const counts = {};
  employees.forEach(function (employee) {
    const date = getEmployeeJoinDate_(employee);
    const year = date ? String(date.getFullYear()) : 'Blank';
    counts[year] = (counts[year] || 0) + 1;
  });
  return Object.keys(counts).sort().map(function (label) {
    const count = counts[label];
    return { label: label, count: count, percent: employees.length ? Math.round((count / employees.length) * 100) + '%' : '0%' };
  });
}

function summarizeAgeRanges_(employees) {
  const ranges = [
    { label: '18-25', min: 18, max: 25, count: 0 },
    { label: '26-35', min: 26, max: 35, count: 0 },
    { label: '36-45', min: 36, max: 45, count: 0 },
    { label: '46-55', min: 46, max: 55, count: 0 },
    { label: '56+', min: 56, max: 200, count: 0 },
    { label: 'Blank', min: null, max: null, count: 0 }
  ];

  employees.forEach(function (employee) {
    const age = parseAgeValue_(employee.ageToDate);
    const range = ranges.find(function (item) {
      return age !== null && item.min !== null && age >= item.min && age <= item.max;
    });
    if (range) range.count++;
    else ranges[ranges.length - 1].count++;
  });

  return ranges.map(function (range) {
    return { label: range.label, count: range.count, percent: employees.length ? Math.round((range.count / employees.length) * 100) + '%' : '0%' };
  });
}

function summarizeDepartmentPerformance_(employees) {
  const groups = {};
  employees.forEach(function (employee) {
    const department = String(employee.department || 'Blank').trim() || 'Blank';
    if (!groups[department]) groups[department] = { count: 0, licensed: 0, salaryTotal: 0, salaryCount: 0 };
    groups[department].count++;
    if (isLicensedEmployee_(employee)) groups[department].licensed++;
    if (Number(employee.basicSalary) > 0) {
      groups[department].salaryTotal += Number(employee.basicSalary);
      groups[department].salaryCount++;
    }
  });

  return Object.keys(groups).sort().map(function (department) {
    const group = groups[department];
    return {
      label: department,
      count: group.count,
      percent: group.count ? Math.round((group.licensed / group.count) * 100) + '%' : '0%',
      details: formatCurrency_(group.salaryCount ? group.salaryTotal / group.salaryCount : 0)
    };
  });
}

function getLatestJoinYear_(employees) {
  const years = employees.map(function (employee) {
    const date = parseFormDate_(employee.employmentDate);
    return date ? date.getFullYear() : null;
  }).filter(Boolean);
  return years.length ? Math.max.apply(null, years) : '';
}

function parseAgeValue_(ageText) {
  const match = String(ageText || '').match(/\d+/);
  return match ? Number(match[0]) : null;
}

function average_(values) {
  const filtered = values.filter(function (value) { return value > 0; });
  return filtered.length ? filtered.reduce(function (sum, value) { return sum + value; }, 0) / filtered.length : 0;
}

function formatCurrency_(value) {
  return 'PHP ' + Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function normalizePictureUrl_(value) {
  const url = String(value || '').trim();
  if (!url) return '';

  const driveFileMatch = url.match(/drive\.google\.com\/file\/d\/([^/]+)/);
  if (driveFileMatch && driveFileMatch[1]) {
    return 'https://drive.google.com/uc?export=view&id=' + driveFileMatch[1];
  }

  const driveOpenMatch = url.match(/[?&]id=([^&]+)/);
  if (url.includes('drive.google.com') && driveOpenMatch && driveOpenMatch[1]) {
    return 'https://drive.google.com/uc?export=view&id=' + driveOpenMatch[1];
  }

  return url;
}

function normalizeLicenseValue_(value) {
  const license = String(value || '').trim();
  return license || 'Without License';
}

function isLicensedEmployee_(employee) {
  const license = normalizeLicenseValue_(employee && employee.license);
  return license.toLowerCase() !== 'without license';
}

function getEmployeePictureFormula_(employee, height, width) {
  const pictureUrl = normalizePictureUrl_(employee.pictureUrl);
  if (pictureUrl) {
    return '=IMAGE("' + escapeFormulaText_(pictureUrl) + '",4,' + (height || 220) + ',' + (width || 220) + ')';
  }

  return '=IMAGE("https://ui-avatars.com/api/?size=220&background=0f766e&color=ffffff&name=' + encodeURIComponent(employee.name || 'Employee') + '")';
}

function getProfilePictureMap_() {
  const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = spreadsheet.getSheetByName(PROFILE_SHEET_NAME);
  const pictures = {};
  if (!sheet || sheet.getRange('A3').getValue() !== 'HRIS Employee Profiles') return pictures;

  const lastRow = sheet.getLastRow();
  for (let row = 7; row <= lastRow; row += 9) {
    const idNumber = String(sheet.getRange(row + 1, 4).getValue() || '').trim();
    if (!idNumber) continue;

    const cell = sheet.getRange(row, 1);
    const formula = cell.getFormula();
    const match = formula.match(/=IMAGE\("([^"]+)"/i);
    if (match && match[1]) {
      pictures[idNumber] = match[1].replace(/""/g, '"');
    } else {
      const pictureUrl = normalizePictureUrl_(cell.getValue()) || normalizePictureUrl_(sheet.getRange(row, 1).getValue());
      if (pictureUrl) pictures[idNumber] = pictureUrl;
    }
  }

  return pictures;
}

function escapeFormulaText_(value) {
  return String(value || '').replace(/"/g, '""');
}

function doesRangeTouchA1_(range, a1Notation) {
  const target = range.getSheet().getRange(a1Notation);
  const rowStart = range.getRow();
  const rowEnd = rowStart + range.getNumRows() - 1;
  const columnStart = range.getColumn();
  const columnEnd = columnStart + range.getNumColumns() - 1;
  const targetRowStart = target.getRow();
  const targetRowEnd = targetRowStart + target.getNumRows() - 1;
  const targetColumnStart = target.getColumn();
  const targetColumnEnd = targetColumnStart + target.getNumColumns() - 1;

  return rowStart <= targetRowEnd &&
    rowEnd >= targetRowStart &&
    columnStart <= targetColumnEnd &&
    columnEnd >= targetColumnStart;
}

function normalizeEmploymentType_(value) {
  return String(value || '').toLowerCase().replace(/[^a-z]/g, '');
}

function formatClassificationForRecord_(value) {
  const type = normalizeEmploymentType_(value);
  if (type === 'fulltime' || type === 'full' || type === 'tft') return 'T/FT';
  if (type === 'parttime' || type === 'part' || type === 'tpt') return 'T/PT';
  if (type === 'nonteaching' || type === 'nt') return 'NT';
  return value || '';
}

function isFullTimeEmployee_(employee) {
  const type = normalizeEmploymentType_(employee.classification);
  return type === 'fulltime' || type === 'full' || type === 'tft' || type === 'nonteaching' || type === 'nt';
}

function isPartTimeEmployee_(employee) {
  const type = normalizeEmploymentType_(employee.classification);
  return type === 'parttime' || type === 'part' || type === 'tpt';
}

function applyColumnWidths_(sheet) {
  const widths = [
    150, 220, 120, 130, 90, 120, 260, 135, 125, 110,
    135, 190, 170, 160, 120, 90, 90, 130, 130, 135,
    145, 145, 210, 190, 190, 155, 150, 150, 145, 125,
    130, 130, 120, 135, 240, 260
  ];
  widths.forEach(function (width, index) {
    sheet.setColumnWidth(index + 1, width);
  });
}

function applyNumberFormatting_(sheet, lastRow) {
  if (lastRow < DATA_START_ROW) return;
  const rowCount = lastRow - HEADER_ROW;
  sheet.getRange(DATA_START_ROW, 1, rowCount, 1).setNumberFormat('mmm d, yyyy h:mm AM/PM');
  sheet.getRange(DATA_START_ROW, 9, rowCount, 1).setNumberFormat('mmm d, yyyy');
  sheet.getRange(DATA_START_ROW, 11, rowCount, 1).setNumberFormat('mmm d, yyyy');
  sheet.getRange(DATA_START_ROW, 29, rowCount, 1).setNumberFormat('mmm d, yyyy');
  sheet.getRange(DATA_START_ROW, 30, rowCount, 1).setNumberFormat('mmm d, yyyy');
  sheet.getRange(DATA_START_ROW, 31, rowCount, 3).setNumberFormat('#,##0.00');
}

function getOrCreateSheet_(sheetName) {
  const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  const exactMatch = spreadsheet.getSheetByName(sheetName);
  if (exactMatch) return exactMatch;

  const lowerName = String(sheetName).toLowerCase();
  const caseInsensitiveMatch = spreadsheet.getSheets().find(function (sheet) {
    return String(sheet.getName()).toLowerCase() === lowerName;
  });

  return caseInsensitiveMatch || spreadsheet.insertSheet(sheetName);
}
