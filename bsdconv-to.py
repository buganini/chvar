
# python chvar . transliterate CP950 | python bsdconv-to.py CP950
import sys
from chvar.helper import p01
from bsdconv import Bsdconv

c = Bsdconv("bsdconv:{0}|byte:hex".format(sys.argv[1]))

for l in sys.stdin:
	l = l.strip()
	if not l:
		continue
	l = l.split("\t")
	b = c.conv(p01(l[1])).decode("utf-8").upper()
	if b:
		sys.stdout.write("{0}\t{1}\n".format(p01(l[0]), b))
	else:
		sys.stderr.write("Converion fail: {}\n".format(l))
