class Group():
	def __init__(self, filename):
		self.data = {}
		self.rdata = {}
		f = open(filename)
		for l in f:
			l = l.strip()
			if l=="":
				continue
			k, v = l.split("\t", 1)
			if not k in self.data:
				self.data[k] = []
			self.data[k].append(v)
			if v not in self.rdata:
				self.rdata[v] = k
			# else:
				# print(u"{0}: Duplicated: {1}".format(filename, v))
		f.close()
