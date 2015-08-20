
# python chvar . fuzzy TW | python bsdconv-inter.py
import sys
from chvar.helper import p01

for l in sys.stdin:
	l = l.strip()
	if not l:
		continue
	l = l.split("\t")
	print("{0}\t{1}".format(p01(l[0]), p01(l[1])))
