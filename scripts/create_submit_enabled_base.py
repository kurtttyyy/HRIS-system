from openpyxl import Workbook

from generate_employee_dashboard_excel import make_employee_data_sheet, make_employee_sheet


wb = Workbook()
default = wb.active
wb.remove(default)
make_employee_data_sheet(wb)
make_employee_sheet(wb)
wb._sheets = [wb["Employee Data Sheet"], wb["Employee"]]
wb.active = 0
wb.calculation.fullCalcOnLoad = True
wb.calculation.forceFullCalc = True
wb.save("employee_submit_enabled_base.xlsx")
print("employee_submit_enabled_base.xlsx")
