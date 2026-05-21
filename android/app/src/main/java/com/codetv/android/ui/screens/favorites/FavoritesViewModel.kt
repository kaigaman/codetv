package com.codetv.android.ui.screens.favorites

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codetv.android.data.api.CodetvRepository
import com.codetv.android.data.model.Channel
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class FavoritesUiState(
    val channels: List<Channel> = emptyList(),
    val isLoading: Boolean = true,
)

@HiltViewModel
class FavoritesViewModel @Inject constructor(
    private val repository: CodetvRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(FavoritesUiState())
    val uiState: StateFlow<FavoritesUiState> = _uiState

    fun loadFavorites() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true)
            try {
                val result = repository.getFavorites()
                _uiState.value = FavoritesUiState(
                    channels = result.getOrNull() ?: emptyList(),
                    isLoading = false,
                )
            } catch (e: Exception) {
                _uiState.value = FavoritesUiState(isLoading = false)
            }
        }
    }
}
