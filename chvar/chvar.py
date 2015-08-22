import re
import sys
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

	def query(self, key):
		if re.match(r"^[0-9A-Fa-f]+$", key):
			key = key.upper()
		elif type(key) != type(u""):
			key = key.decode("utf-8").encode("unicode_escape")[2:].upper()

		g = self.layers[0][0].rdata.get(key)
		if not g:
			print("No Data.")
			return

		g2 = self.layers[1][0].rdata.get(g)
		if not g2:
			self._print_group1(g)
			return

		self._print_group2(g2)

	def _print_group2(self, g2):
		c = Bsdconv("bsdconv:utf-8")

		print("#Layer 2 Group {0}\n".format(g2))

		print("##Attributes")
		a = self.layers[1][1].get(g2)
		for cat in a:
			v = a[cat]
			print("* {0}: {1} ({2})".format(cat, c.conv(p01(v)), v))

		print("##Member")
		for g in self.layers[1][0].data.get(g2):
			self._print_group1(g)

	def _print_group1(self, g):
		c = Bsdconv("bsdconv:utf-8")

		print("###Layer 1 Group {0}\n".format(g))

		print("####Attributes")
		a = self.layers[0][1].get(g)
		for cat in a:
			v = a[cat]
			print("  * {0}: {1} ({2})".format(cat, c.conv(p01(v)), v))

		print("####Member")
		d = self.layers[0][0].data.get(g)
		for e in d:
			print("  * {0} ({1})".format(c.conv(p01(e)), e))

		print("")
