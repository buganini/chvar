import os
import sys

from group import Group
from attr import Attr
from chvar import Chvar

def usage():
	print("python chvar . transliterate {CP950,CP936,GB2312,GBK}\n")
	print("python chvar . normalize {TW,CN,JP}\n")
	print("python chvar . fuzzy {TW,CN,JP}\n")
	print("python chvar . query {試,9644}\n")
	sys.exit()

if len(sys.argv) < 4:
	usage()

attr1 = Attr(os.path.join(sys.argv[1], "attr1.txt"))
group1 = Group(os.path.join(sys.argv[1], "group1.txt"))

attr2 = Attr(os.path.join(sys.argv[1], "attr2.txt"))
group2 = Group(os.path.join(sys.argv[1], "group2.txt"))

chvar = Chvar((group1, attr1), (group2, attr2))

argv = {"transliterate":("CP950","CP936","GB2312","GBK"), "normalize":("TW","CN","JP"), "fuzzy":("TW","CN","JP")}

if sys.argv[2] == "query":
	chvar.query(sys.argv[3])
else:
	if sys.argv[2] not in ("transliterate", "normalize", "fuzzy"):
		usage()

	if sys.argv[3] not in argv[sys.argv[2]]:
		usage()

	chvar.dump(sys.argv[2], sys.argv[3])
