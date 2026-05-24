from openpyxl import load_workbook

source = "employee_dashboard_with_data_entry_fixed.xlsx"
target = "employee_dashboard_submit_macro_base.xlsx"

wb = load_workbook(source)
for ws in wb.worksheets:
    ws._images = []
    ws._charts = []
wb.save(target)
print(target)
