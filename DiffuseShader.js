
var DiffuseShader = Class.create(Shader, {
    // Constructor
    initialize: function($super, lightsPos, lightsColor, lightsIntensity) {
		$super();
        this.setUniform('lightsPos', 'v3v', lightsPos);
		this.setUniform('lightsColor', 'v4v', lightsColor);
		this.setUniform('lightsIntensity', 'fv1', lightsIntensity);
		this.loadShader('shaders/texturedWorldCoords.vert', 'vertex');
		this.loadShader('shaders/diffuseMaterial.frag', 'fragment');
    },
	use: function(matDiffuse, matDiffuseColor, diffMap) {
		this.setUniform('matDiffuse', 'f', matDiffuse);
		this.setUniform('matDiffuseColor', 'v4', matDiffuseColor);
		this.setUniform('isTextured', 'i', diffMap == null ? 0 : 1);
		this.setUniform('diffMap', 't', diffMap);
	}
});
