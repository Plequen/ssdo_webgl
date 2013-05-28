#ifdef GL_ES
precision highp float;
#endif

// Material properties
uniform float matDiffuse;
uniform vec4 matDiffuseColor;
uniform sampler2D texture;
uniform int isTextured;

//varying vec2 vUv;

void main() {
	gl_FragColor = vec4((matDiffuse * matDiffuseColor).xyz, 1.0);
	//if (isTextured == 1)
		//gl_FragColor *= texture2D(texture, vUv);
}
