from openpyxl import Workbook

wb = Workbook()
ws = wb.active
ws.title = "Employee Data Sheet"
ws["A1"] = "test"
wb.save("_openpyxl_com_test.xlsx")
