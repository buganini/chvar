def p01(d):
	p = ""
	if len(d) % 2:
		p = "0"
	return "01" + p + d

def inBMP(cp):
	return int(cp, 16) <= 0xFFFF
