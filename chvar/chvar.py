from bsdconv import Bsdconv
from helper import *

class Chvar():
	def __init__(self, *layers):
		self.layers = layers

	def normalize(self, k, cat):
		g = self.layers[0][0].rdata.get(k, None)
		if g:
			return self.layers[0][1].get(g).get(cat, k)
		else:
			return k

	def fuzzy(self, k, cat):
		ret = k
		gk = k
		for layer in self.layers:
			gk = layer[0].rdata.get(gk, None)
			if gk:
				ret = layer[1].get(gk).get(cat, ret)
			else:
				break
		return ret

	def transliterate(self, k, cat):
		ret = k
		gk = k
		c = Bsdconv("bsdconv:{0}".format(cat))
		c.conv(p01(k))
		if not c.counter("OERR"):
			return ret
		for layer in self.layers:
			gk = layer[0].rdata.get(gk)
			t = layer[1].get(gk).get(cat, None)
			if t:
				ret = t
				break
			else:
				gk = layer[0].rdata.get(gk, None)
		return ret

	def dump(self , action, cat):
		func = {"normalize":self.normalize, "fuzzy":self.fuzzy, "transliterate":self.transliterate}.get(action)
		keys = sorted(self.layers[0][0].rdata.keys())
		for k in keys:
			r = func(k, cat)
			if r != k:
				print("{0}\t{1}".format(k, r))
