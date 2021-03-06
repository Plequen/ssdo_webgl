<!--
	Simple WebGL application implementing several screen-space effects via deferred shading.
	Shadow Mapping (hard shadows) - Variance Shadow Mapping - Depth of Field - Motion Blur - SSAO - SSDO.
	Authors : Quentin Plessis, Antoine Toisoul
-->
<!DOCTYPE html>
<html>
	<head>
		<title>WebGL SSDO</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<script src="lib/dat.gui.min.js">// control panel ui</script>
		<script src="lib/prototype.js">// class framework</script>
		<script src="lib/jquery-1.9.1.min.js"></script>
		<script src="lib/three.js"></script>
		<script src="lib/Detector.js">// webgl capacities detector</script>
		<script src="lib/TrackballControls.js"></script>
		<!--<script src="lib/FirstPersonControls.js"></script>
		<script src="lib/FlyControls.js"></script>
		<script src="lib/PointerLockControls.js"></script>-->
		<script src="lib/stats.min.js"></script>
		<script src="lib/MTLLoaderP.js"></script>
		<script src="lib/OBJMTLLoader.js"></script>
		<script src="Random.js"></script>
		<script src="OFF.js">// off loader</script>
		<script src="OBJ.js">// obj loader</script>
		<script src="Shader.js"></script>
		<script src="PhongShader.js"></script>
		<script src="ExpressiveShader.js"></script>
		<script src="DiffuseShader.js"></script>
		<script src="Light.js"></script>
		<script src="DisplayManager.js"></script>
		<script src="Scene.js"></script>
		<link rel="stylesheet" href="css/main.css" />  
	</head>
	<body>
		<!-- Container of the webgl rendering -->
		<div id="container"></div>
	</body>
	<script src="Params.js">// Parameters definition</script>
	<script src="Controls.js">// Control panel</script>
	<!-- Scenes description -->
	<script src="scenes/Scene1.js"></script>
	<script src="scenes/SceneSponza.js"></script>
	<script src="scenes/Scene3.js"></script>
	<script src="scenes/SceneCornel.js"></script>
	<script src="scenes/SceneSquirrel.js"></script>
	<script src="scenes/SceneBird.js"></script>
	<script src="scenes/SceneOasis.js"></script>
	<script src="Initialization.js">// Initialization script, called by init()</script>
	<script>
		<?php
			if (isset($_GET['scene'])) {
		?>
			currentScene = '<?php echo htmlspecialchars($_GET['scene']); ?>';
		<?php
			}
			if (isset($_GET['mode'])) {
		?>
			MODE = '<?php echo htmlspecialchars($_GET['mode']); ?>';
		<?php
			}
		?>
		// initialization
		init();
		$.getJSON("controls.json", function(json) {
			initControls(json);
		});
		// launch the demo
		animate();
		
		/**
		 * Called when the resolution of the shadow maps is changed
		 */
		function refactorShadowMaps() {
			var lLength = lights.length;
			while (lLength--)
				shadowMaps[lLength] = new THREE.WebGLRenderTarget(shadowMapsResolution, shadowMapsResolution, options);
			shadowMapAux = new THREE.WebGLRenderTarget(shadowMapsFullResolution, shadowMapsFullResolution, options);
			shadowMapAux2 = new THREE.WebGLRenderTarget(shadowMapsFullResolution, shadowMapsFullResolution, options);
			shadowsShader.setUniform('shadowMap', 't', shadowMaps[0]);
			shadowsShader.setUniform('shadowMap1', 't', shadowMaps[1]);
			ssdoDirectLightingShader.setUniform('shadowMap', 't', shadowMaps[0]);
			ssdoDirectLightingShader.setUniform('shadowMap1', 't', shadowMaps[1]);
			ssdoIndirectBounceShader.setUniform('shadowMap', 't', shadowMaps[0]);
			ssdoIndirectBounceShader.setUniform('shadowMap1', 't', shadowMaps[1]);
			displayManager.addCustomTexture(shadowMaps[0], 'shaders/displayShadowMap.frag', 'shadowMap1');
			displayManager.addCustomTexture(shadowMaps[1], 'shaders/displayShadowMap.frag', 'shadowMap2');
			displayManager.organize();
		}
		
		/**
		 * Keyboard events
		 */
		function keyDown(event) {
			//alert(event.keyCode);
			if (event.keyCode == 65) // a
				ANIMATION = !ANIMATION;
			else if (event.keyCode == 79) { // o
				MODE = 'all';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 78) { // n
				MODE = 'normal';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode >= 97 && event.keyCode <= 105) { // 1 to 9
				displayManager.showViewInGrid(event.keyCode - 97);
			}
			else if (event.keyCode == 80) { // p
				MODE = 'normal';
				displayManager.display({names: ['phong']});
			}
			else if (event.keyCode == 69) { // e
				MODE = 'normal';
				displayManager.display({names: ['expressive']});
			}
			else if (event.keyCode == 83) { // s
				MODE = 'shadows';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 68) { // d
				MODE = 'ssdo';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 84) { // t
				MODE = 'texture';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 82) { // r
				MODE = 'random';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 70) { // f
				MODE = 'dof';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 77) { // m
				MODE = 'motionBlur';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 86) { // v
				MODE = 'ssdo90';
				displayManager.display(customDisplays[MODE]);
			}
			else if (event.keyCode == 37) { // left
				if (!displayManager.previousView()) { MODE = 'all'; } render();
			}
			else if (event.keyCode == 39) { // right
				if (!displayManager.nextView()) { MODE = 'all'; } render();
			}
			else if (event.keyCode == 67) { // c
				fizzyText.lightControl = !fizzyText.lightControl;
				if (fizzyText.lightControl)
					initCameraControls(lightsCameras[fizzyText.selectedLight]);
				else
					initCameraControls(camera);
			}
			render();
		}
		
		var outof = 0;
		/**
		 * Rendering loop
		 */
		function render() {
			/* DOES NOT WORK YET
			// geometry buffer computing
			if (MODE == 'all' || MODE == 'shadowsG') {
				var i = objects.length;
				while (i--) {
					if (objects[i].composedObject)
						objects[i].setShaderMaterial(geometryBufferShader);
					else {
						geometryBufferShader.setUniform('matDiffuse', 'f', materials[i]['matDiffuse']);
						geometryBufferShader.setUniform('matDiffuseColor', 'v4', materials[i]['matDiffuseColor']);
						geometryBufferShader.setUniform('isTextured', 'i', materials[i]['texture'] != null ? 1 : 0);
						geometryBufferShader.setUniform('diffMap', 't', materials[i]['texture']);
						objects[i].setMaterial(geometryBufferShader.createMaterial());
					}
				}
				renderer.render(scene, camera, geometryBufferTexture, true);
			}*/
			
			// 1st rendering : computes depth and normals
			if (MODE == 'all' || MODE == 'normal' || MODE == 'shadows' || MODE == 'ssdo' || MODE == 'ssao' || MODE == 'ssdo90' || MODE == 'dof' || MODE == 'motionBlur') {
				var i = objects.length;
				while (i--)
					objects[i].setMaterial(normalsAndDepthShader.createMaterial());
				renderer.render(scene, camera, normalsAndDepthTexture, true);
				if (enableMultipleViews)
				{
					renderer.render(scene, camera90, normalsAndDepthTexture90, true);
				}				
			}
			
			// 2nd rendering : diffuse map rendering
			if (MODE == 'all' || MODE == 'normal' || MODE == 'shadows' || MODE == 'ssdo' || MODE == 'ssao' || MODE == 'ssdo90' || MODE == 'texture' || MODE == 'dof' || MODE == 'motionBlur') {
				var i = objects.length;
				while (i--) {
					if (objects[i].composedObject)
						objects[i].setShaderMaterial(diffuseMapShader);
					else {
						diffuseMapShader.setUniform('matDiffuse', 'f', materials[i]['matDiffuse']);
						diffuseMapShader.setUniform('matDiffuseColor', 'v4', materials[i]['matDiffuseColor']);
						diffuseMapShader.setUniform('isTextured', 'i', materials[i]['texture'] != null ? 1 : 0);
						diffuseMapShader.setUniform('diffMap', 't', materials[i]['texture']);
						objects[i].setMaterial(diffuseMapShader.createMaterial());
					}
				}
				renderer.render(scene, camera, diffuseTexture, true);
				if(enableMultipleViews)
				{
					renderer.render(scene, camera90, diffuseTexture90, true);
				}
			}
			
			// specular map rendering
			if (MODE == 'all' || MODE == 'normal' || MODE == 'shadows' || MODE == 'dof' || MODE == 'motionBlur') {
				var i = objects.length;
				while (i--) {
					if (objects[i].composedObject)
						objects[i].setShaderMaterial(specularMapShader);
					else {
						specularMapShader.setUniform('matSpecular', 'f', materials[i]['matSpecular']);
						specularMapShader.setUniform('matSpecularColor', 'v4', materials[i]['matSpecularColor']);
						specularMapShader.setUniform('shininess', 'f', materials[i]['shininess']);
						//specularMapShader.setUniform('isTextured', 'i', 0);
						//specularMapShader.setUniform('texture', 't', materials[i]['texture']);
						objects[i].setMaterial(specularMapShader.createMaterial());
					}
				}
				renderer.render(scene, camera, specularTexture, true);
			}
			
			// 3rd rendering : diffuse rendering with lights
			if (MODE == 'all' || MODE == 'normal') {
				var i = objects.length;
				while (i--) {
					if (objects[i].composedObject)
						objects[i].setShaderMaterial(shaders['diffuse']);
					else {
						shaders['diffuse'].use(materials[i]['matDiffuse'], materials[i]['matDiffuseColor'], materials[i]['texture']);
						objects[i].setMaterial(shaders['diffuse'].createMaterial());
					}
				}
				renderer.render(scene, camera, rtTextures['diffuse'], true);
			}
			
			// 4th rendering : phong rendering
			if (MODE == 'all' || MODE == 'normal' || MODE == 'shadows') {
				var i = objects.length;
				while (i--) {
					if (objects[i].composedObject) {
						shaders['phong'].use(materials[i]['matDiffuse'], materials[i]['matSpecular'], materials[i]['shininess'], materials[i]['matDiffuseColor'], materials[i]['matSpecularColor'], materials[i]['matEmissive'], materials[i]['matEmissiveColor'], materials[i]['texture']);
						objects[i].setShaderMaterial(shaders['phong']);
					}
					else {
						shaders['phong'].use(materials[i]['matDiffuse'], materials[i]['matSpecular'], materials[i]['shininess'], materials[i]['matDiffuseColor'], materials[i]['matSpecularColor'], materials[i]['matEmissive'], materials[i]['matEmissiveColor'], materials[i]['texture']);
						objects[i].setMaterial(shaders['phong'].createMaterial());
					}
				}
				renderer.render(scene, camera, rtTextures['phong'], true);
			}
			
			// 5th rendering : cartoon rendering
			if (MODE == 'all') { // || MODE == 'normal') {
				var i = objects.length;
				while (i--) {
					if (objects[i].composedObject) {
						shaders['expressive'].use(materials[i]['matDiffuse'], materials[i]['shininess'], materials[i]['matDiffuseColor']);
						objects[i].setShaderMaterial(shaders['expressive']);
					}
					else {
						shaders['expressive'].use(materials[i]['matDiffuse'], materials[i]['shininess'], materials[i]['matDiffuseColor']);
						objects[i].setMaterial(shaders['expressive'].createMaterial());
					}
				}
				renderer.render(scene, camera, rtTextures['expressive'], true);
			}
			
			// 6th rendering : computes 3D coords
			if (MODE == 'all' || MODE == 'shadows' || MODE == 'dof' || MODE == 'motionBlur' || MODE == 'ssdo' || MODE == 'ssdo90' || MODE == 'ssao') {
				var i = objects.length;
				while (i--)
					objects[i].setMaterial(coordsShader.getMaterial());
				renderer.render(scene, camera, coordsTexture, true);
				if (enableMultipleViews)
				{
					renderer.render(scene, camera90, coordsTexture90, true);
				}
			}
			
			// 7th rendering : compute second depth
			if (MODE == 'all' || MODE == 'ssdo' || MODE == 'ssao') {
				var mat = secondDepthShader.createMaterial();
				mat.side = THREE.DoubleSide;
				var i = objects.length;
				while (i--)
					objects[i].setMaterial(mat);
				renderer.render(scene, camera, secondDepthTexture, true);

			}

			// Shadow maps
			if (MODE == 'all' || MODE == 'shadows' || MODE == 'ssdo' || MODE == 'ssdo90' || MODE == 'dof' || MODE == 'motionBlur') {
				var i = objects.length;
				while (i--)
					objects[i].setMaterial(shadowMapsShader.createMaterial());
				if (shadowMode == 1 && blurShadowMaps) {
					var i = lights.length;
					while (i--) {
						renderer.render(scene, lightsCameras[i], shadowMapAux, true);
						shadowMapBlurShader.setUniform('shadowMap', 't', shadowMapAux);
						shadowMapBlurShader.setUniform('texelDirection', 'v2', new THREE.Vector2(1.0 / shadowMapsFullResolution, 0.0));
						smQuad.setMaterial(shadowMapBlurShader.createMaterial());
						renderer.render(smScene, cameraRTT, shadowMapAux2, true);
						
						shadowMapBlurShader.setUniform('shadowMap', 't', shadowMapAux2);
						shadowMapBlurShader.setUniform('texelDirection', 'v2', new THREE.Vector2(0.0, 1.0 / shadowMapsFullResolution));
						smQuad.setMaterial(shadowMapBlurShader.createMaterial());
						renderer.render(smScene, cameraRTT, shadowMaps[i], true);
					}
				}
				else {
					var i = lights.length;
					while (i--)
						renderer.render(scene, lightsCameras[i], shadowMaps[i], true);
				}
			}
			
			// Shadows
			if (MODE == 'all' || MODE == 'shadows' || MODE == 'dof' || MODE == 'motionBlur') {
				screenSpaceQuad.setMaterial(shadowsShader.createMaterial());
				renderer.render(screenSpaceScene, cameraRTT, shadowsTexture, true);
			}
			if (MODE == 'all' || MODE == 'shadowsG') {
				screenSpaceQuad.setMaterial(shadowsGShader.createMaterial());
				renderer.render(screenSpaceScene, cameraRTT, shadowsTexture, true);
			}
			
			// Motion blur
			if (MODE == 'all' || MODE == 'motionBlur') {
				velocityShader.setUniform('previousViewMatrix', 'm4', previousCameraViewMatrix);
				var i = objects.length;
				while (i--) {
					if (i.composedObject)
						objects[i].setShaderMaterial(velocityShader);
					else {
						velocityShader.setUniform('previousModelMatrix', 'm4', objects[i].previousModelMatrix);
						objects[i].setMaterial(velocityShader.createMaterial());
					}
				}
				renderer.render(scene, camera, velocityTexture, true);
				screenSpaceQuad.setMaterial(motionBlurShader.createMaterial());
				renderer.render(screenSpaceScene, cameraRTT, motionBlurTexture, true);
				
				if (outof >= 0) {
					var i = objects.length;
					while (i--) {
						if (i.composedObject)
							objects[i].updateModelMatrix();
						else
							objects[i].previousModelMatrix.copy(objects[i].matrixWorld);
					}
					//console.log(JSON.stringify(previousCameraViewMatrix) + " \n  " + JSON.stringify(camera.matrixWorldInverse) + "\n\n");
					previousCameraViewMatrix.copy(camera.matrixWorldInverse);
					outof = 0;
				}
				outof++;
			}
			
			// Depth of field
			if (MODE == 'all' || MODE == 'dof') {
				// blur
				DOFBlurShader.setUniform('texelDirection', 'v2', new THREE.Vector2(1.0 / renderingWidth, 0.0));
				DOFBlurShader.setUniform('colorTexture', 't', shadowsTexture);
				dofQuad.setMaterial(DOFBlurShader.createMaterial());
				renderer.render(dofScene, cameraRTT, dofAuxTexture, true);
			
				DOFBlurShader.setUniform('texelDirection', 'v2', new THREE.Vector2(0.0, 1.0 / dofResolution));
				DOFBlurShader.setUniform('colorTexture', 't', dofAuxTexture);
				dofQuad.setMaterial(DOFBlurShader.createMaterial());
				renderer.render(dofScene, cameraRTT, DOFBlurTexture, true);
			
				// composited image
				DOFImageShader.setUniform('colorTexture', 't', shadowsTexture);
				dofQuad.setMaterial(DOFImageShader.createMaterial());
				renderer.render(dofScene, cameraRTT, DOFImageTexture, true);
			}
			
			// SSAO
			if (MODE == 'all' || MODE == 'ssao') {
				// ssao Only
				ssaoQuad.setMaterial(ssaoOnlyShader.createMaterial());
				renderer.render(ssaoScene, cameraRTT, ssaoOnlyBuffer, true);

				// ssao blur pass
				ssdoBlurShader.setUniform('ssdoBuffer', 't', ssaoOnlyBuffer);
				ssdoBlurShader.setUniform('texelOffset', 'v2', new THREE.Vector2(1.0/window.innerWidth, 0));
				ssdoQuad.setMaterial(ssdoBlurShader.createMaterial());
				renderer.render(ssdoScene, cameraRTT, ssaoBlurAuxBuffer, true);
			
				ssdoBlurShader.setUniform('ssdoBuffer', 't', ssaoBlurAuxBuffer);
				ssdoBlurShader.setUniform('texelOffset', 'v2', new THREE.Vector2(0.0,1.0/window.innerHeight));
				ssdoQuad.setMaterial(ssdoBlurShader.createMaterial());
				renderer.render(ssdoScene, cameraRTT, ssaoBlurBuffer, true);
			
				// ssao with diffuse color
				ssaoQuad.setMaterial(ssaoDiffuseShader.createMaterial());
				renderer.render(ssaoScene, cameraRTT, ssaoDiffuseBuffer, true);
			}

			// SSDO
			if (MODE == 'all' || MODE == 'ssdo' || MODE == 'ssdo90') {
				// ssdo 1st pass
				ssdoDirectLightingShader.setUniform('positionsBuffer', 't', coordsTexture);
				ssdoDirectLightingShader.setUniform('normalsAndDepthBuffer', 't', normalsAndDepthTexture);
				ssdoDirectLightingShader.setUniform('cameraProjectionM', 'm4', camera.projectionMatrix);
				ssdoDirectLightingShader.setUniform('cameraViewMatrix', 'm4', camera.matrixWorldInverse);
				ssdoDirectLightingShader.setUniform('diffuseTexture', 't', diffuseTexture);
				ssdoQuad.setMaterial(ssdoDirectLightingShader.createMaterial());

				renderer.render(ssdoScene, cameraRTT, directLightBuffer, true);
		
				if(enableMultipleViews)
				{	
					ssdoDirectLightingShader.setUniform('positionsBuffer', 't', coordsTexture90);
					ssdoDirectLightingShader.setUniform('normalsAndDepthBuffer', 't', normalsAndDepthTexture90);
					ssdoDirectLightingShader.setUniform('cameraProjectionM', 'm4', camera90.projectionMatrix);
					ssdoDirectLightingShader.setUniform('cameraViewMatrix', 'm4', camera90.matrixWorldInverse);
					ssdoDirectLightingShader.setUniform('diffuseTexture', 't', diffuseTexture90);
					ssdoQuad.setMaterial(ssdoDirectLightingShader.createMaterial());

					renderer.render(ssdoScene, cameraRTT, directLightBuffer90, true);
				}
				// ssdo 2nd pass
				ssdoQuad.setMaterial(ssdoIndirectBounceShader.createMaterial());
				renderer.render(ssdoScene, cameraRTT, ssdoIndirectBounceBuffer, true);	

				// ssdo blur pass
				ssdoBlurShader.setUniform('ssdoBuffer', 't', ssdoIndirectBounceBuffer);
				ssdoBlurShader.setUniform('texelOffset', 'v2', new THREE.Vector2(1.0/window.innerWidth, 0));
				ssdoQuad.setMaterial(ssdoBlurShader.createMaterial());
				renderer.render(ssdoScene, cameraRTT, ssdoBlurAuxBuffer, true);
			
				ssdoBlurShader.setUniform('ssdoBuffer', 't', ssdoBlurAuxBuffer);
				ssdoBlurShader.setUniform('texelOffset', 'v2', new THREE.Vector2(0.0,1.0/window.innerHeight));
				ssdoQuad.setMaterial(ssdoBlurShader.createMaterial());
				renderer.render(ssdoScene, cameraRTT, ssdoBlurBuffer, true);
			
				// ssdo final pass
				ssdoQuad.setMaterial(ssdoFinalShader.createMaterial());
				renderer.render(ssdoScene, cameraRTT, ssdoFinalBuffer, true);
			}
			
			// displays the scene
			renderer.render(sceneScreen, cameraRTT);
		}
		
		// requestAnim shim layer by Paul Irish
		window.requestAnimFrame = (function() {
			return	window.requestAnimationFrame       || 
					window.webkitRequestAnimationFrame || 
					window.mozRequestAnimationFrame    || 
					window.oRequestAnimationFrame      || 
					window.msRequestAnimationFrame     || 
					function(/* function */ callback, /* DOMElement */ element){
						window.setTimeout(callback, 1000 / 60);
					};
		})();

		// animation variables
		var direction = 1
		var lastTime = (new Date()).getTime();
		var currentTime = lastTime;
		var interval = 20;
		
		// squirrels
		var lastChoiceTime = currentTime;
		var randomObj = 1;
		// birds
		var birdTheta = 0, theta0 = - Math.PI / 12;
		var birdR = 100.0, r01 = 10.0, r02 = -10.0;
		var birdSelfTheta = 0.0;
		
		/**
		 * Animation loop
		 */
		function animate() {
			requestAnimationFrame(animate);
			currentTime = (new Date()).getTime();
			if (ANIMATION) {
				if (currentTime - lastTime >= interval) {
					if (currentScene == "scene1") {
						if (objects[0].position.y >= 100)
							direction = -1;
						if (objects[0].position.y <= -50)
							direction = 1;
						objects[0].position.y += direction * 10.0;
						//objects[2].rotation.y += direction * 5 / 100;
					}
					else if (currentScene == "squirrel") {
						if (currentTime - lastChoiceTime >= 1000) {
							objects[randomObj].position.y = 0;
							randomObj = Math.round(1.0 + Math.random() * 20.0);
							lastChoiceTime = currentTime;
						}
						if (objects[randomObj].position.y >= 50)
							direction = -1;
						if (objects[randomObj].position.y <= 0)
							direction = 1;
						objects[randomObj].position.y += direction * 10.0;
					}
					else if (currentScene == "bird") {
						objects[1].position.x = birdR * Math.cos(birdTheta);
						objects[1].position.z = birdR * Math.sin(birdTheta);
						objects[1].rotation.y = - birdTheta;
						objects[2].position.x = (r01 + birdR) * Math.cos(theta0 + birdTheta);
						objects[2].position.z = (r01 + birdR) * Math.sin(theta0 + birdTheta);
						objects[2].rotation.y = - birdTheta;
						objects[3].position.x = (r02 + birdR) * Math.cos(theta0 + birdTheta);
						objects[3].position.z = (r02 + birdR) * Math.sin(theta0 + birdTheta);
						objects[3].rotation.y = - birdTheta;
						birdTheta += Math.PI / 40;
					}
					lastTime = currentTime;
					render();
				}
			}
		
			camera90.position.x = -camera.position.x;	
			camera90.position.y = camera.position.y;	
			camera90.position.z = camera.position.z;
			camera90.rotation.x = camera.rotation.x;
			camera90.rotation.y = camera.rotation.y;
			camera90.rotation.z = camera.rotation.z;
			camera90.lookAt(new THREE.Vector3(0.0,0.0,0.0));	
			//Rotation de 90° camera
		//	var rotationMatrix90 = new THREE.Matrix4().makeRotationAxis(camera.up, -Math.PI/2);
		//	camera90.applyMatrix(rotationMatrix90);			
		//	camera90.matrixWorldInverse = camera.matrixWorldInverse;			
		//	camera90.matrixWorld = camera.matrixWorld;			
			controls.update(clock.getDelta());
			stats.update();
		}
		
		function onWindowResize() {
			renderingWidth = window.innerWidth;
			renderingHeight = window.innerHeight;
			
			camera.aspect = window.innerWidth / window.innerHeight;
			camera.updateProjectionMatrix();

			renderer.setSize(renderingWidth, renderingHeight);
			controls.handleResize();

			render();
		}
	</script>
</html>
