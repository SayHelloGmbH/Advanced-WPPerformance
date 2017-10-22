import settings from '../../../../../assets/settings.json';

for (let key in settings) {
	window[key] = settings[key];
}