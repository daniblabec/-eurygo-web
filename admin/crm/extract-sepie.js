/**
 * EuryGo CRM — Extractor PDF SEPIE
 * Extrae centros KA121-SCH y KA122-SCH del PDF de provisión de fondos
 *
 * Uso: node extract-sepie.js <ruta-al-pdf> [salida.json]
 * Ejemplo: node extract-sepie.js "../../Centros provisión de fondos.crdownload" centros_sepie.json
 */

const pdfjsLib = require('pdfjs-dist/legacy/build/pdf.js');
const fs = require('fs');
const path = require('path');

// ─── Coordenadas de Jerez ───
const JEREZ = { lat: 36.6850, lng: -6.1260 };

// ─── Tabla de municipios → coordenadas + provincia ───
// Cubre capitales de provincia + municipios relevantes de Cádiz
const MUNICIPIOS = {
  // ── ANDALUCÍA ──
  'almeria': { lat: 36.8381, lng: -2.4597, prov: 'Almería', ccaa: 'Andalucía' },
  'cadiz': { lat: 36.5271, lng: -6.2886, prov: 'Cádiz', ccaa: 'Andalucía' },
  'cordoba': { lat: 37.8882, lng: -4.7794, prov: 'Córdoba', ccaa: 'Andalucía' },
  'granada': { lat: 37.1773, lng: -3.5986, prov: 'Granada', ccaa: 'Andalucía' },
  'huelva': { lat: 37.2614, lng: -6.9447, prov: 'Huelva', ccaa: 'Andalucía' },
  'jaen': { lat: 37.7796, lng: -3.7849, prov: 'Jaén', ccaa: 'Andalucía' },
  'malaga': { lat: 36.7213, lng: -4.4214, prov: 'Málaga', ccaa: 'Andalucía' },
  'sevilla': { lat: 37.3891, lng: -5.9845, prov: 'Sevilla', ccaa: 'Andalucía' },
  'jerez de la frontera': { lat: 36.6850, lng: -6.1260, prov: 'Cádiz', ccaa: 'Andalucía' },
  'jerez': { lat: 36.6850, lng: -6.1260, prov: 'Cádiz', ccaa: 'Andalucía' },
  'algeciras': { lat: 36.1408, lng: -5.4536, prov: 'Cádiz', ccaa: 'Andalucía' },
  'la linea de la concepcion': { lat: 36.1676, lng: -5.3493, prov: 'Cádiz', ccaa: 'Andalucía' },
  'la linea': { lat: 36.1676, lng: -5.3493, prov: 'Cádiz', ccaa: 'Andalucía' },
  'el puerto de santa maria': { lat: 36.5935, lng: -6.2330, prov: 'Cádiz', ccaa: 'Andalucía' },
  'sanlucar de barrameda': { lat: 36.7767, lng: -6.3531, prov: 'Cádiz', ccaa: 'Andalucía' },
  'chiclana de la frontera': { lat: 36.4193, lng: -6.1491, prov: 'Cádiz', ccaa: 'Andalucía' },
  'chiclana': { lat: 36.4193, lng: -6.1491, prov: 'Cádiz', ccaa: 'Andalucía' },
  'san fernando': { lat: 36.4775, lng: -6.1990, prov: 'Cádiz', ccaa: 'Andalucía' },
  'los barrios': { lat: 36.1870, lng: -5.4970, prov: 'Cádiz', ccaa: 'Andalucía' },
  'tarifa': { lat: 36.0143, lng: -5.6044, prov: 'Cádiz', ccaa: 'Andalucía' },
  'rota': { lat: 36.6261, lng: -6.3542, prov: 'Cádiz', ccaa: 'Andalucía' },
  'arcos de la frontera': { lat: 36.7511, lng: -5.8069, prov: 'Cádiz', ccaa: 'Andalucía' },
  'marbella': { lat: 36.5099, lng: -4.8824, prov: 'Málaga', ccaa: 'Andalucía' },
  'estepona': { lat: 36.4268, lng: -5.1474, prov: 'Málaga', ccaa: 'Andalucía' },
  'mijas': { lat: 36.5960, lng: -4.6370, prov: 'Málaga', ccaa: 'Andalucía' },
  'mijas costa las lagunas': { lat: 36.5960, lng: -4.6370, prov: 'Málaga', ccaa: 'Andalucía' },
  'fuengirola': { lat: 36.5441, lng: -4.6250, prov: 'Málaga', ccaa: 'Andalucía' },
  'torremolinos': { lat: 36.6218, lng: -4.4996, prov: 'Málaga', ccaa: 'Andalucía' },
  'benalmadena': { lat: 36.5985, lng: -4.5169, prov: 'Málaga', ccaa: 'Andalucía' },
  'antequera': { lat: 37.0194, lng: -4.5614, prov: 'Málaga', ccaa: 'Andalucía' },
  'velez-malaga': { lat: 36.7838, lng: -4.1038, prov: 'Málaga', ccaa: 'Andalucía' },
  'ronda': { lat: 36.7422, lng: -5.1665, prov: 'Málaga', ccaa: 'Andalucía' },
  'dos hermanas': { lat: 37.2838, lng: -5.9227, prov: 'Sevilla', ccaa: 'Andalucía' },
  'alcala de guadaira': { lat: 37.3382, lng: -5.8382, prov: 'Sevilla', ccaa: 'Andalucía' },
  'utrera': { lat: 37.1858, lng: -5.7812, prov: 'Sevilla', ccaa: 'Andalucía' },
  'ecija': { lat: 37.5414, lng: -5.0826, prov: 'Sevilla', ccaa: 'Andalucía' },
  'lebrija': { lat: 36.9218, lng: -6.0779, prov: 'Sevilla', ccaa: 'Andalucía' },
  'motril': { lat: 36.7509, lng: -3.5184, prov: 'Granada', ccaa: 'Andalucía' },
  'linares': { lat: 38.0935, lng: -3.6365, prov: 'Jaén', ccaa: 'Andalucía' },
  'ubeda': { lat: 38.0133, lng: -3.3705, prov: 'Jaén', ccaa: 'Andalucía' },
  'baeza': { lat: 37.9937, lng: -3.4712, prov: 'Jaén', ccaa: 'Andalucía' },
  'roquetas de mar': { lat: 36.7643, lng: -2.6149, prov: 'Almería', ccaa: 'Andalucía' },
  'el ejido': { lat: 36.7764, lng: -2.8138, prov: 'Almería', ccaa: 'Andalucía' },
  'lucena': { lat: 37.4085, lng: -4.4850, prov: 'Córdoba', ccaa: 'Andalucía' },
  'bollullos par del condado': { lat: 37.3408, lng: -6.5363, prov: 'Huelva', ccaa: 'Andalucía' },
  'moguer': { lat: 37.2752, lng: -6.8385, prov: 'Huelva', ccaa: 'Andalucía' },
  'marmolejo': { lat: 38.0500, lng: -4.1716, prov: 'Jaén', ccaa: 'Andalucía' },
  'huetor tajar': { lat: 37.1910, lng: -4.0530, prov: 'Granada', ccaa: 'Andalucía' },
  'alfacar': { lat: 37.2360, lng: -3.5670, prov: 'Granada', ccaa: 'Andalucía' },
  'la carlota': { lat: 37.6710, lng: -4.9400, prov: 'Córdoba', ccaa: 'Andalucía' },
  'torreperogil': { lat: 38.0440, lng: -3.2850, prov: 'Jaén', ccaa: 'Andalucía' },
  'cantoria': { lat: 37.3430, lng: -2.1450, prov: 'Almería', ccaa: 'Andalucía' },
  'estacion de cartama': { lat: 36.7480, lng: -4.6310, prov: 'Málaga', ccaa: 'Andalucía' },

  // ── COMUNIDAD DE MADRID ──
  'madrid': { lat: 40.4168, lng: -3.7038, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'alcala de henares': { lat: 40.4818, lng: -3.3635, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'leganes': { lat: 40.3279, lng: -3.7647, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'getafe': { lat: 40.3088, lng: -3.7310, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'mostoles': { lat: 40.3249, lng: -3.8650, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'fuenlabrada': { lat: 40.2838, lng: -3.7978, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'alcorcon': { lat: 40.3455, lng: -3.8277, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'torrejon de ardoz': { lat: 40.4568, lng: -3.4828, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'parla': { lat: 40.2382, lng: -3.7672, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'boadilla del monte': { lat: 40.4056, lng: -3.8760, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },
  'rivas-vaciamadrid': { lat: 40.3529, lng: -3.5421, prov: 'Madrid', ccaa: 'Comunidad de Madrid' },

  // ── CATALUÑA ──
  'barcelona': { lat: 41.3851, lng: 2.1734, prov: 'Barcelona', ccaa: 'Cataluña' },
  'hospitalet de llobregat': { lat: 41.3596, lng: 2.0997, prov: 'Barcelona', ccaa: 'Cataluña' },
  'badalona': { lat: 41.4500, lng: 2.2474, prov: 'Barcelona', ccaa: 'Cataluña' },
  'terrassa': { lat: 41.5630, lng: 2.0089, prov: 'Barcelona', ccaa: 'Cataluña' },
  'sabadell': { lat: 41.5487, lng: 2.1095, prov: 'Barcelona', ccaa: 'Cataluña' },
  'girona': { lat: 41.9794, lng: 2.8214, prov: 'Girona', ccaa: 'Cataluña' },
  'tarragona': { lat: 41.1189, lng: 1.2445, prov: 'Tarragona', ccaa: 'Cataluña' },
  'lleida': { lat: 41.6176, lng: 0.6200, prov: 'Lleida', ccaa: 'Cataluña' },
  'reus': { lat: 41.1559, lng: 1.1069, prov: 'Tarragona', ccaa: 'Cataluña' },
  'mataro': { lat: 41.5407, lng: 2.4445, prov: 'Barcelona', ccaa: 'Cataluña' },
  'cornella de llobregat': { lat: 41.3533, lng: 2.0722, prov: 'Barcelona', ccaa: 'Cataluña' },
  'sant feliu de llobregat': { lat: 41.3789, lng: 2.0447, prov: 'Barcelona', ccaa: 'Cataluña' },
  'santa coloma de gramenet': { lat: 41.4516, lng: 2.2085, prov: 'Barcelona', ccaa: 'Cataluña' },
  'el prat de llobregat': { lat: 41.3246, lng: 2.0953, prov: 'Barcelona', ccaa: 'Cataluña' },
  'cambrils': { lat: 41.0685, lng: 1.0571, prov: 'Tarragona', ccaa: 'Cataluña' },
  'sta. margarida de montbui': { lat: 41.5680, lng: 1.6090, prov: 'Barcelona', ccaa: 'Cataluña' },
  'odena': { lat: 41.6000, lng: 1.6500, prov: 'Barcelona', ccaa: 'Cataluña' },

  // ── COMUNIDAD VALENCIANA ──
  'valencia': { lat: 39.4699, lng: -0.3763, prov: 'Valencia', ccaa: 'Comunidad Valenciana' },
  'alicante': { lat: 38.3452, lng: -0.4815, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'castellon de la plana': { lat: 39.9860, lng: -0.0371, prov: 'Castellón', ccaa: 'Comunidad Valenciana' },
  'elche': { lat: 38.2669, lng: -0.6983, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'torrevieja': { lat: 37.9786, lng: -0.6823, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'torrent': { lat: 39.4372, lng: -0.4654, prov: 'Valencia', ccaa: 'Comunidad Valenciana' },
  'gandia': { lat: 38.9674, lng: -0.1818, prov: 'Valencia', ccaa: 'Comunidad Valenciana' },
  'benidorm': { lat: 38.5411, lng: -0.1225, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'ondara': { lat: 38.8271, lng: 0.0119, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'aspe': { lat: 38.3455, lng: -0.7676, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'san vicente del raspeig': { lat: 38.3965, lng: -0.5254, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'ibi': { lat: 38.6268, lng: -0.5721, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'petrer': { lat: 38.4850, lng: -0.7720, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'sedavi': { lat: 39.4251, lng: -0.3828, prov: 'Valencia', ccaa: 'Comunidad Valenciana' },
  'manises': { lat: 39.4905, lng: -0.4627, prov: 'Valencia', ccaa: 'Comunidad Valenciana' },
  'vila-real': { lat: 39.9380, lng: -0.1010, prov: 'Castellón', ccaa: 'Comunidad Valenciana' },
  'san isidro': { lat: 38.1890, lng: -0.8350, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'salinas': { lat: 38.5140, lng: -0.8830, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'benejuzar': { lat: 38.0815, lng: -0.8425, prov: 'Alicante', ccaa: 'Comunidad Valenciana' },
  'manuel': { lat: 39.0670, lng: -0.4600, prov: 'Valencia', ccaa: 'Comunidad Valenciana' },

  // ── ARAGÓN ──
  'zaragoza': { lat: 41.6488, lng: -0.8891, prov: 'Zaragoza', ccaa: 'Aragón' },
  'huesca': { lat: 42.1401, lng: -0.4089, prov: 'Huesca', ccaa: 'Aragón' },
  'teruel': { lat: 40.3456, lng: -1.1065, prov: 'Teruel', ccaa: 'Aragón' },
  'binefar': { lat: 41.8590, lng: 0.2990, prov: 'Huesca', ccaa: 'Aragón' },
  'utebo': { lat: 41.7120, lng: -0.9940, prov: 'Zaragoza', ccaa: 'Aragón' },
  'zuera': { lat: 41.8700, lng: -0.7870, prov: 'Zaragoza', ccaa: 'Aragón' },
  'la almunia de dona godina': { lat: 41.4810, lng: -1.3780, prov: 'Zaragoza', ccaa: 'Aragón' },

  // ── CASTILLA Y LEÓN ──
  'valladolid': { lat: 41.6523, lng: -4.7245, prov: 'Valladolid', ccaa: 'Castilla y León' },
  'burgos': { lat: 42.3439, lng: -3.6969, prov: 'Burgos', ccaa: 'Castilla y León' },
  'salamanca': { lat: 40.9701, lng: -5.6635, prov: 'Salamanca', ccaa: 'Castilla y León' },
  'leon': { lat: 42.5987, lng: -5.5671, prov: 'León', ccaa: 'Castilla y León' },
  'palencia': { lat: 42.0096, lng: -4.5288, prov: 'Palencia', ccaa: 'Castilla y León' },
  'zamora': { lat: 41.5034, lng: -5.7447, prov: 'Zamora', ccaa: 'Castilla y León' },
  'segovia': { lat: 40.9429, lng: -4.1088, prov: 'Segovia', ccaa: 'Castilla y León' },
  'avila': { lat: 40.6563, lng: -4.6816, prov: 'Ávila', ccaa: 'Castilla y León' },
  'soria': { lat: 41.7665, lng: -2.4790, prov: 'Soria', ccaa: 'Castilla y León' },
  'cisterniga': { lat: 41.6210, lng: -4.6850, prov: 'Valladolid', ccaa: 'Castilla y León' },
  'cuellar': { lat: 41.4030, lng: -4.3170, prov: 'Segovia', ccaa: 'Castilla y León' },
  'valsain': { lat: 40.8780, lng: -4.0370, prov: 'Segovia', ccaa: 'Castilla y León' },

  // ── CASTILLA-LA MANCHA ──
  'toledo': { lat: 39.8628, lng: -4.0273, prov: 'Toledo', ccaa: 'Castilla-La Mancha' },
  'ciudad real': { lat: 38.9848, lng: -3.9274, prov: 'Ciudad Real', ccaa: 'Castilla-La Mancha' },
  'albacete': { lat: 38.9942, lng: -1.8585, prov: 'Albacete', ccaa: 'Castilla-La Mancha' },
  'guadalajara': { lat: 40.6337, lng: -3.1660, prov: 'Guadalajara', ccaa: 'Castilla-La Mancha' },
  'cuenca': { lat: 40.0704, lng: -2.1374, prov: 'Cuenca', ccaa: 'Castilla-La Mancha' },
  'talavera de la reina': { lat: 39.9635, lng: -4.8305, prov: 'Toledo', ccaa: 'Castilla-La Mancha' },
  'caudete': { lat: 38.7080, lng: -1.2830, prov: 'Albacete', ccaa: 'Castilla-La Mancha' },

  // ── EXTREMADURA ──
  'badajoz': { lat: 38.8794, lng: -6.9706, prov: 'Badajoz', ccaa: 'Extremadura' },
  'caceres': { lat: 39.4753, lng: -6.3724, prov: 'Cáceres', ccaa: 'Extremadura' },
  'merida': { lat: 38.9161, lng: -6.3438, prov: 'Badajoz', ccaa: 'Extremadura' },
  'plasencia': { lat: 40.0301, lng: -6.0901, prov: 'Cáceres', ccaa: 'Extremadura' },
  'don benito': { lat: 38.9558, lng: -5.8624, prov: 'Badajoz', ccaa: 'Extremadura' },
  'zafra': { lat: 38.4176, lng: -6.4181, prov: 'Badajoz', ccaa: 'Extremadura' },
  'llerena': { lat: 38.2360, lng: -6.0200, prov: 'Badajoz', ccaa: 'Extremadura' },
  'villanueva de la serena': { lat: 38.9740, lng: -5.7970, prov: 'Badajoz', ccaa: 'Extremadura' },

  // ── REGIÓN DE MURCIA ──
  'murcia': { lat: 37.9922, lng: -1.1307, prov: 'Murcia', ccaa: 'Región de Murcia' },
  'cartagena': { lat: 37.6257, lng: -0.9966, prov: 'Murcia', ccaa: 'Región de Murcia' },
  'lorca': { lat: 37.6774, lng: -1.7009, prov: 'Murcia', ccaa: 'Región de Murcia' },
  'totana': { lat: 37.7680, lng: -1.5040, prov: 'Murcia', ccaa: 'Región de Murcia' },
  'bullas': { lat: 38.0500, lng: -1.6700, prov: 'Murcia', ccaa: 'Región de Murcia' },
  'los alcazares': { lat: 37.7430, lng: -0.8490, prov: 'Murcia', ccaa: 'Región de Murcia' },
  'puerto de mazarron': { lat: 37.5670, lng: -1.2610, prov: 'Murcia', ccaa: 'Región de Murcia' },

  // ── PAÍS VASCO ──
  'bilbao': { lat: 43.2630, lng: -2.9350, prov: 'Vizcaya', ccaa: 'País Vasco' },
  'san sebastian': { lat: 43.3183, lng: -1.9812, prov: 'Guipúzcoa', ccaa: 'País Vasco' },
  'vitoria-gasteiz': { lat: 42.8467, lng: -2.6726, prov: 'Álava', ccaa: 'País Vasco' },
  'vitoria': { lat: 42.8467, lng: -2.6726, prov: 'Álava', ccaa: 'País Vasco' },
  'mutriku': { lat: 43.3070, lng: -2.3860, prov: 'Guipúzcoa', ccaa: 'País Vasco' },

  // ── GALICIA ──
  'a coruna': { lat: 43.3713, lng: -8.3958, prov: 'A Coruña', ccaa: 'Galicia' },
  'vigo': { lat: 42.2406, lng: -8.7207, prov: 'Pontevedra', ccaa: 'Galicia' },
  'santiago de compostela': { lat: 42.8782, lng: -8.5448, prov: 'A Coruña', ccaa: 'Galicia' },
  'ourense': { lat: 42.3380, lng: -7.8639, prov: 'Ourense', ccaa: 'Galicia' },
  'lugo': { lat: 43.0097, lng: -7.5567, prov: 'Lugo', ccaa: 'Galicia' },
  'pontevedra': { lat: 42.4310, lng: -8.6447, prov: 'Pontevedra', ccaa: 'Galicia' },
  'ferrol': { lat: 43.4849, lng: -8.2328, prov: 'A Coruña', ccaa: 'Galicia' },

  // ── CANARIAS ──
  'las palmas de gran canaria': { lat: 28.1235, lng: -15.4363, prov: 'Las Palmas', ccaa: 'Canarias' },
  'santa cruz de tenerife': { lat: 28.4636, lng: -16.2518, prov: 'S/C de Tenerife', ccaa: 'Canarias' },
  'la laguna': { lat: 28.4853, lng: -16.3159, prov: 'S/C de Tenerife', ccaa: 'Canarias' },
  'icod de los vinos': { lat: 28.3670, lng: -16.7190, prov: 'S/C de Tenerife', ccaa: 'Canarias' },
  'granadilla de abona': { lat: 28.1240, lng: -16.5720, prov: 'S/C de Tenerife', ccaa: 'Canarias' },

  // ── ISLAS BALEARES ──
  'palma': { lat: 39.5696, lng: 2.6502, prov: 'Baleares', ccaa: 'Illes Balears' },
  'palma de mallorca': { lat: 39.5696, lng: 2.6502, prov: 'Baleares', ccaa: 'Illes Balears' },
  'eivissa': { lat: 38.9067, lng: 1.4206, prov: 'Baleares', ccaa: 'Illes Balears' },
  'felanitx': { lat: 39.4690, lng: 3.1480, prov: 'Baleares', ccaa: 'Illes Balears' },

  // ── NAVARRA ──
  'pamplona': { lat: 42.8125, lng: -1.6458, prov: 'Navarra', ccaa: 'Comunidad Foral de Navarra' },

  // ── LA RIOJA ──
  'logrono': { lat: 42.4627, lng: -2.4449, prov: 'La Rioja', ccaa: 'La Rioja' },

  // ── CANTABRIA ──
  'santander': { lat: 43.4623, lng: -3.8100, prov: 'Cantabria', ccaa: 'Cantabria' },
  'san vicente de la barquera': { lat: 43.3850, lng: -4.3980, prov: 'Cantabria', ccaa: 'Cantabria' },

  // ── ASTURIAS ──
  'oviedo': { lat: 43.3614, lng: -5.8493, prov: 'Asturias', ccaa: 'Principado de Asturias' },
  'gijon': { lat: 43.5322, lng: -5.6611, prov: 'Asturias', ccaa: 'Principado de Asturias' },
};

// ─── CCAA mapping from PDF text (normalized) ───
const CCAA_MAP = {
  'andalucia': 'Andalucía',
  'aragon': 'Aragón',
  'principado de asturias': 'Principado de Asturias',
  'asturias': 'Principado de Asturias',
  'illes balears': 'Illes Balears',
  'islas baleares': 'Illes Balears',
  'canarias': 'Canarias',
  'cantabria': 'Cantabria',
  'castilla y leon': 'Castilla y León',
  'castilla-la mancha': 'Castilla-La Mancha',
  'cataluna': 'Cataluña',
  'catalunya': 'Cataluña',
  'comunidad valenciana': 'Comunidad Valenciana',
  'comunitat valenciana': 'Comunidad Valenciana',
  'extremadura': 'Extremadura',
  'galicia': 'Galicia',
  'comunidad de madrid': 'Comunidad de Madrid',
  'region de murcia': 'Región de Murcia',
  'comunidad foral de navarra': 'Comunidad Foral de Navarra',
  'navarra': 'Comunidad Foral de Navarra',
  'pais vasco': 'País Vasco',
  'euskadi': 'País Vasco',
  'la rioja': 'La Rioja',
  'ceuta': 'Ceuta',
  'melilla': 'Melilla',
};

// Province to CCAA fallback
const PROV_CCAA = {
  'Almería': 'Andalucía', 'Cádiz': 'Andalucía', 'Córdoba': 'Andalucía',
  'Granada': 'Andalucía', 'Huelva': 'Andalucía', 'Jaén': 'Andalucía',
  'Málaga': 'Andalucía', 'Sevilla': 'Andalucía',
  'Zaragoza': 'Aragón', 'Huesca': 'Aragón', 'Teruel': 'Aragón',
  'Asturias': 'Principado de Asturias',
  'Baleares': 'Illes Balears',
  'Las Palmas': 'Canarias', 'S/C de Tenerife': 'Canarias',
  'Cantabria': 'Cantabria',
  'Ávila': 'Castilla y León', 'Burgos': 'Castilla y León', 'León': 'Castilla y León',
  'Palencia': 'Castilla y León', 'Salamanca': 'Castilla y León', 'Segovia': 'Castilla y León',
  'Soria': 'Castilla y León', 'Valladolid': 'Castilla y León', 'Zamora': 'Castilla y León',
  'Albacete': 'Castilla-La Mancha', 'Ciudad Real': 'Castilla-La Mancha',
  'Cuenca': 'Castilla-La Mancha', 'Guadalajara': 'Castilla-La Mancha', 'Toledo': 'Castilla-La Mancha',
  'Barcelona': 'Cataluña', 'Girona': 'Cataluña', 'Lleida': 'Cataluña', 'Tarragona': 'Cataluña',
  'Alicante': 'Comunidad Valenciana', 'Castellón': 'Comunidad Valenciana', 'Valencia': 'Comunidad Valenciana',
  'Badajoz': 'Extremadura', 'Cáceres': 'Extremadura',
  'A Coruña': 'Galicia', 'Lugo': 'Galicia', 'Ourense': 'Galicia', 'Pontevedra': 'Galicia',
  'Madrid': 'Comunidad de Madrid',
  'Murcia': 'Región de Murcia',
  'Navarra': 'Comunidad Foral de Navarra',
  'Álava': 'País Vasco', 'Guipúzcoa': 'País Vasco', 'Vizcaya': 'País Vasco',
  'La Rioja': 'La Rioja',
};

// ─── Haversine distance ───
function haversine(lat1, lng1, lat2, lng2) {
  const R = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLng = (lng2 - lng1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLng / 2) ** 2;
  return R * 2 * Math.asin(Math.sqrt(a));
}

// ─── Normalize text for lookup ───
function normalize(str) {
  return str.toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9 ]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

// ─── Lookup municipality ───
function lookupMunicipio(localidad) {
  const norm = normalize(localidad);

  // Direct match
  if (MUNICIPIOS[norm]) return MUNICIPIOS[norm];

  // Try without parenthetical info: "ALFACAR (GRANADA)" → "alfacar"
  const noParens = norm.replace(/\s*\(.*?\)\s*/g, '').trim();
  if (MUNICIPIOS[noParens]) return MUNICIPIOS[noParens];

  // Try removing province suffix: "VALLADOLID (ESPAÑA)" → "valladolid"
  const parts = noParens.split(/\s+/);
  if (parts.length > 1) {
    // Try progressively shorter prefixes
    for (let i = parts.length - 1; i >= 1; i--) {
      const sub = parts.slice(0, i).join(' ');
      if (MUNICIPIOS[sub]) return MUNICIPIOS[sub];
    }
  }

  return null;
}

// ─── Deduce center type from name ───
function deduceTipo(nombre) {
  const n = nombre.toUpperCase();
  if (/^I\.?E\.?S\.?\s|^INSTITUTO\b/.test(n)) return 'IES';
  if (/^C\.?E\.?I\.?P\.?\s|^CEIP\b|^COLEGIO PUBLICO|^COLEGIO P[ÚU]BLICO/.test(n)) return 'CEIP';
  if (/^C\.?P\.?\s/.test(n) && !/^C\.?P\.?E/.test(n)) return 'CP';
  if (/CIFP|^CENTRO INTEGRADO/.test(n)) return 'CIFP';
  if (/^EOI\b|ESCUELA OFICIAL DE IDIOMAS/.test(n)) return 'EOI';
  if (/CONSERVATORIO/.test(n)) return 'CONSERVATORIO';
  if (/^CEPA\b|EDUCACI[ÓO]N.*ADULTAS|^EPA\b/.test(n)) return 'EPA';
  if (/\bFP\b|FORMACI[ÓO]N PROFESIONAL/.test(n)) return 'FP';
  if (/^ESCOLA\b|^INSTITUT\b|^INS\b/.test(n)) return 'CEIP'; // Catalan schools
  if (/^CC\.?\s|^COLEGIO\b|^FUNDACI[ÓO]N/.test(n)) return 'CP'; // concertado
  return 'OTRO';
}

// ─── Deduce titularidad ───
function deduceTitularidad(nombre) {
  const n = nombre.toUpperCase();
  if (/P[ÚU]BLICO|^IES\b|^I\.E\.S|^CEIP\b|^C\.E\.I\.P|^CEBEP\b|^CEPA\b|^CIFP\b|^EOI\b/.test(n)) return 'publico';
  if (/^ESCOLA\b|^INSTITUT\b|^INS\b/.test(n)) return 'publico';
  if (/COLEGIO\b|FUNDACI[ÓO]N|^CC\.?\s|SALESIANO|MARISTA|PILAR|CONSOLACI[ÓO]N|HUERTO|^LA GALIA/.test(n)) return 'concertado';
  return 'nd';
}

// ─── Main extraction ───
async function extractPDF(pdfPath) {
  const data = new Uint8Array(fs.readFileSync(pdfPath));
  const doc = await pdfjsLib.getDocument({ data }).promise;
  console.log(`Total pages: ${doc.numPages}`);

  const centros = [];
  let currentAnexo = null;
  const anexosTarget = ['KA121-SCH', 'KA122-SCH'];
  const municipiosNoReconocidos = new Set();

  for (let i = 1; i <= doc.numPages; i++) {
    const page = await doc.getPage(i);
    const content = await page.getTextContent();
    const text = content.items.map(item => item.str).join(' ');

    // Detect which annex we're in
    if (text.includes('KA121-SCH') && /admitidas/i.test(text)) {
      if (!/excluidas/i.test(text.substring(text.indexOf('KA121-SCH')))) {
        currentAnexo = 'KA121-SCH';
      }
    }
    if (text.includes('KA122-SCH') && /admitidas/i.test(text)) {
      if (!/excluidas/i.test(text.substring(text.indexOf('KA122-SCH')))) {
        currentAnexo = 'KA122-SCH';
      }
    }
    // Switch off for VET, HED, ADU, excluidas sections
    if (/KA1[23]\d-VET|KA130|KA131|KA171|KA122-ADU|KA121-ADU/.test(text) &&
        !text.includes('KA121-SCH') && !text.includes('KA122-SCH')) {
      currentAnexo = null;
    }
    if (/excluidas/i.test(text) && !(/admitidas/i.test(text))) {
      currentAnexo = null;
    }

    if (!anexosTarget.includes(currentAnexo)) continue;
    if (i <= 4) continue; // Skip intro pages

    // Extract entries using regex on the full text
    // Pattern: KA1XX-SCH-XXXXXXXX followed by project number, then name, then locality, then CCAA
    const pattern = /(KA1\d{2}-SCH-[A-F0-9]{8})\s+(2026-1-ES01-KA1\d{2}-SCH-\d+)\s+/g;
    let match;
    const entries = [];

    while ((match = pattern.exec(text)) !== null) {
      entries.push({
        numSolicitud: match[1],
        numProyecto: match[2],
        startIdx: match.index + match[0].length,
      });
    }

    // For each entry, extract name + locality + CCAA from the text between this entry and the next
    for (let e = 0; e < entries.length; e++) {
      const start = entries[e].startIdx;
      const end = e + 1 < entries.length
        ? text.lastIndexOf(entries[e + 1].numSolicitud, entries[e + 1].startIdx)
        : text.length;

      let remainder = text.substring(start, end).trim();

      // Remove leading number (the Nº of next entry)
      remainder = remainder.replace(/\s+\d+\s*$/, '').trim();

      // Try to extract CCAA from end of remainder
      let ccaaFound = null;
      let localidadFound = null;
      let nombreCentro = remainder;

      // Known CCAA strings that appear in the PDF
      const ccaaStrings = [
        'Andalucía', 'Aragón', 'Principado de Asturias', 'Illes Balears',
        'Canarias', 'Cantabria', 'Castilla y León', 'Castilla-La Mancha',
        'Cataluña', 'Comunidad Valenciana', 'Comunidad de Madrid',
        'Extremadura', 'Galicia', 'Región de Murcia',
        'Comunidad Foral de Navarra', 'País Vasco', 'La Rioja',
        'Ceuta', 'Melilla',
      ];

      for (const ccaa of ccaaStrings) {
        const idx = remainder.lastIndexOf(ccaa);
        if (idx > 0) {
          ccaaFound = ccaa;
          remainder = remainder.substring(0, idx).trim();
          break;
        }
      }

      // If CCAA wasn't found with exact match, try normalized
      if (!ccaaFound) {
        const normRem = normalize(remainder);
        for (const [key, val] of Object.entries(CCAA_MAP)) {
          if (normRem.endsWith(key)) {
            ccaaFound = val;
            remainder = remainder.substring(0, remainder.length - key.length).trim();
            break;
          }
        }
      }

      // Now separate name from locality
      // The locality is typically the last words - we need heuristic here
      // For now, store the full remainder as nombre and try to extract locality
      nombreCentro = remainder;

      // Try to find municipality by checking last N words
      const words = remainder.split(/\s+/);
      for (let w = Math.min(5, words.length - 1); w >= 1; w--) {
        const candidate = words.slice(-w).join(' ');
        const lookup = lookupMunicipio(candidate);
        if (lookup) {
          localidadFound = candidate;
          nombreCentro = words.slice(0, -w).join(' ');
          break;
        }
      }

      // If no locality found via lookup, take last 1-2 words as locality
      if (!localidadFound && words.length > 2) {
        // Try last 2 words, then last 1
        localidadFound = words.slice(-1).join(' ');
        nombreCentro = words.slice(0, -1).join(' ');
      }

      // Clean up name
      nombreCentro = nombreCentro.replace(/^\d+\s*/, '').trim();

      // Geocode
      const geoData = localidadFound ? lookupMunicipio(localidadFound) : null;
      let lat = null, lng = null, provincia = null, ccaaGeo = null, distancia = null;

      if (geoData) {
        lat = geoData.lat;
        lng = geoData.lng;
        provincia = geoData.prov;
        ccaaGeo = geoData.ccaa;
        distancia = Math.round(haversine(JEREZ.lat, JEREZ.lng, lat, lng) * 100) / 100;
      } else if (localidadFound) {
        municipiosNoReconocidos.add(localidadFound);
      }

      // Use PDF's CCAA if we have it, else geocoded
      const ccaaFinal = ccaaFound || ccaaGeo || null;

      // Calculate priority
      let prioridad = 'baja';
      if (ccaaFinal === 'Andalucía') {
        prioridad = 'alta';
      } else if (distancia !== null && distancia < 300) {
        prioridad = 'media';
      } else if (['Extremadura', 'Región de Murcia', 'Castilla-La Mancha'].includes(ccaaFinal)) {
        prioridad = 'media';
      }

      // Type of meeting
      const tipoReunion = (distancia !== null && distancia < 150) ? 'presencial' : 'telematica';

      centros.push({
        num_solicitud: entries[e].numSolicitud,
        num_proyecto: entries[e].numProyecto,
        tipo_accion: currentAnexo,
        nombre_centro: nombreCentro,
        municipio: localidadFound,
        provincia,
        comunidad: ccaaFinal,
        lat, lng,
        distancia_jerez_km: distancia,
        tipo_centro: deduceTipo(nombreCentro),
        titularidad: deduceTitularidad(nombreCentro),
        tipo_reunion: tipoReunion,
        prioridad,
      });
    }

    // Progress indicator
    if (i % 50 === 0) {
      process.stderr.write(`  Procesadas ${i}/${doc.numPages} páginas, ${centros.length} centros...\r`);
    }
  }

  return { centros, municipiosNoReconocidos: [...municipiosNoReconocidos] };
}

// ─── Run ───
const pdfFile = process.argv[2] || path.join(__dirname, '..', '..', '..', 'Captacion de clientes', 'Centros provisión de fondos.crdownload');
const outputFile = process.argv[3] || path.join(__dirname, 'centros_sepie.json');

console.log(`Leyendo: ${pdfFile}`);
console.log(`Salida:  ${outputFile}\n`);

extractPDF(pdfFile).then(result => {
  fs.writeFileSync(outputFile, JSON.stringify(result, null, 2), 'utf8');

  // Stats
  const { centros, municipiosNoReconocidos } = result;
  const ka121 = centros.filter(c => c.tipo_accion === 'KA121-SCH').length;
  const ka122 = centros.filter(c => c.tipo_accion === 'KA122-SCH').length;
  const alta = centros.filter(c => c.prioridad === 'alta').length;
  const media = centros.filter(c => c.prioridad === 'media').length;
  const baja = centros.filter(c => c.prioridad === 'baja').length;
  const presencial = centros.filter(c => c.tipo_reunion === 'presencial').length;
  const telematica = centros.filter(c => c.tipo_reunion === 'telematica').length;
  const geocoded = centros.filter(c => c.lat !== null).length;

  console.log(`\n${'═'.repeat(50)}`);
  console.log(`EXTRACCIÓN COMPLETADA`);
  console.log(`${'═'.repeat(50)}`);
  console.log(`Total centros extraídos: ${centros.length}`);
  console.log(`  KA121-SCH (acreditación): ${ka121}`);
  console.log(`  KA122-SCH (corta duración): ${ka122}`);
  console.log(`\nPor prioridad:`);
  console.log(`  🔴 Alta (Andalucía): ${alta}`);
  console.log(`  🟡 Media (< 300 km): ${media}`);
  console.log(`  🟢 Baja (resto España): ${baja}`);
  console.log(`\nPor tipo de reunión:`);
  console.log(`  🏢 Presencial (< 150 km): ${presencial}`);
  console.log(`  💻 Telemática (≥ 150 km): ${telematica}`);
  console.log(`\nGeocodificados: ${geocoded}/${centros.length}`);
  console.log(`Municipios no reconocidos: ${municipiosNoReconocidos.length}`);
  if (municipiosNoReconocidos.length > 0) {
    console.log(`  ${municipiosNoReconocidos.slice(0, 30).join(', ')}${municipiosNoReconocidos.length > 30 ? '...' : ''}`);
  }
  console.log(`\nArchivo guardado: ${outputFile}`);
}).catch(err => {
  console.error('Error:', err);
  process.exit(1);
});
