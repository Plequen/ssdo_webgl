var FizzyText = function() {
	this.message = 'Webgl';
	// views
	this.gridDisplayed = 'overview';
	this.viewDisplayed = 'phong';
	this.nextView = function() { if (!displayManager.nextView()) { MODE = 'all'; } render(); };
	this.previousView = function() { if (!displayManager.previousView()) { MODE = 'all'; } render(); };
	// lights
	this.allLights = false;
	this.selectedLight = 0;
	this.lightControl = false;
	this.lightColor = "#ffffff";
	this.lightIntensity = lightDefaultIntensity;
	this.lightAngle = lightDefaultAngle;
	this.skyLightIntensity = skyLightIntensity;
	this.lightAttenuation = lightDefaultAttenuation;
	// shadows
	this.mapsResolution = shadowMapsResolution;
};

function initControls() {
	var text = new FizzyText();
	var gui = new dat.GUI();
	//gui.remember(text);
	
	gui.add(text, 'message');
	
	var displaysFolder = gui.addFolder('Views');
	displaysFolder.add(text, 'gridDisplayed', {'Texture': 'textured', 'Overview': 'all', 'Normal': 'normal', 'Shadows': 'shadows', 'SSDO': 'ssdo'}).name('Grid').onChange(function(value) {
		MODE = value;
		displayManager.display(customDisplays[MODE]);
		render();
	});
	displaysFolder.add(text, 'viewDisplayed', {'Texture': 'diffuseMap', 'Phong': 'phong', 'Expressive': 'expressive', 'Hard shadows': 'hardShadows'}).name('View').onChange(function(value) {
		MODE = 'all';
		displayManager.display({names: [text.viewDisplayed]});
		render();
	});
	displaysFolder.add(text, 'nextView').name('Next view');
	displaysFolder.add(text, 'previousView').name('Previous view');
	displaysFolder.open();
	
	var lightsFolder = gui.addFolder('Lights');
	lightsFolder.add(text, 'skyLightIntensity', 0, 1).step(0.05).name('Sky light').onChange(function(value) {
		skyLightIntensity = value;
		hardShadowsShader.setUniform('skyLightIntensity', 'f', skyLightIntensity);
		render();
	});
	lightsFolder.add(text, 'allLights').name('All');
	var lightsToSelect = {};
	for (var i = 0 ; i < lights.length ; i++)
		lightsToSelect["Light " + i] = i;
	lightsFolder.add(text, 'selectedLight', lightsToSelect).name('Light').onChange(function(value) {
		if (text.lightControl)
			initCameraControls(lightsCameras[value]);
	});
	lightsFolder.add(text, 'lightControl').name('Take control').onChange(function(value) {
		if (value)
			initCameraControls(lightsCameras[text.selectedLight]);
		else
			initCameraControls(camera);
	});
	lightsFolder.addColor(text, 'lightColor').name('Color').onChange(function(value) {
		if (text.allLights)
			for (var i = 0 ; i < lights.length ; i++) {
				lights[i].setHexaColor(value);
				lightsColor[i] = lights[i].getColor();
			}
		else {
			lights[text.selectedLight].setHexaColor(value);
			lightsColor[text.selectedLight] = lights[text.selectedLight].getColor();
		}
		render();
	});
	lightsFolder.add(text, 'lightIntensity', 0.0, 2.0).name('Intensity').onChange(function(value) {
		if (text.allLights)
			for (var i = 0 ; i < lights.length ; i++)
				lightsIntensity[i] = value;
		else
			lightsIntensity[text.selectedLight] = value;
		render();
	});
	lightsFolder.add(text, 'lightAngle', 0.0, 360.0).name('Angle').onChange(function(value) {
		if (text.allLights)
			for (var i = 0 ; i < lights.length ; i++)
				lightsAngle[i] = value;
		else
			lightsAngle[text.selectedLight] = value;
		render();
	});
	lightsFolder.add(text, 'lightAttenuation', 0.0, 5.0).name('Attenuation').onChange(function(value) {
		if (text.allLights)
			for (var i = 0 ; i < lights.length ; i++)
				lightsAttenuation[i] = value;
		else
			lightsAttenuation[text.selectedLight] = value;
		render();
	});
	lightsFolder.open();
	
	var shadowsFolder = gui.addFolder("Shadows");
	shadowsFolder.add(text, 'mapsResolution', 0, 512).name("Shadow maps resolution").onChange(function(value) {
		shadowMapsResolution = Math.round(value);
		refactorShadowMaps();
		render();
	});
	shadowsFolder.open();
}