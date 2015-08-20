class Attr():
	def __init__(self, filename):
		self.data = {}
		f = open(filename)
		self.header = f.readline().strip().split("\t")[1:]
		for l in f:
			l = l.strip()
			if l=="":
				continue
			l = l .strip().split("\t")
			g = l[0]
			l = l[1:]
			if g not in self.data:
				self.data[g] = {}
			for i, k in enumerate(self.header):
				if i<len(l) and l[i]:
					self.data[g][k] = l[i]

	def get(self, g):
		return self.data.get(g, {})
